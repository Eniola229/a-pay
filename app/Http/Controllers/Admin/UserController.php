<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use App\Models\Admin;
use Hash;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Models\User;
use App\Models\Transaction;
use App\Models\GeneralNotification;
use App\Models\Balance;
use App\Models\ContactInquiry;
use App\Models\Errors;
use Illuminate\Support\Facades\Http;
use App\Models\Borrow;
use App\Models\KycProfile;
use App\Models\WhatsappMessage;
use App\Models\Logged;
use Illuminate\Validation\Rule;
use Carbon\Carbon;


class UserController extends Controller
{
    
    public function users(Request $request)
    {
        $query = User::with('balance')->orderBy('created_at', 'desc');
        
        // Check if there's a search query
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('mobile', 'LIKE', "%{$searchTerm}%");
            });
        }
        
        $users = $query->paginate(20);
        
        return view('admin.users', compact('users'));
    }

    public function showUser($id)
    {
        // Fetch user details
        $user = User::findOrFail($id);
        
        // Fetch balance 
        $balance = $user->balance;
        
        // Fetch transactions
        $transactions = Transaction::where('user_id', $id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        // Fetch loans
        $loans = Borrow::where('user_id', $id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        // Fetch user kyc
        $kyc = KycProfile::where('user_id', $user->id)->first();
        
        // Fetch WhatsApp messages for this user
        $whatsappMessages = WhatsappMessage::where('phone_number', $user->mobile)
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'messages_page');
        
        // Fetch ALL logs for this user
        $logs = Logged::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'logs_page');
        
        return view('admin.user-details', compact('user', 'balance', 'transactions', 'loans', 'kyc', 'whatsappMessages', 'logs'));
    }
    public function approve(Kyc $kyc) {
        $kyc->update(['status' => 'approved', 'rejection_reason' => null]);
        return back()->with('success', 'KYC approved successfully');
    }

    public function reject(Request $request, Kyc $kyc) {
        $request->validate(['rejection_reason' => 'required|string']);
        $kyc->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason
        ]);
        return back()->with('success', 'KYC rejected');
    }

    public function delete(Kyc $kyc) {
        // Optional: Only allow deletion if status is rejected
        if ($kyc->status !== 'rejected') {
            return back()->with('error', 'Only rejected KYC can be deleted');
        }
        
        $kyc->delete();
        return back()->with('success', 'KYC deleted successfully');
    }

    public function updateUser(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Get the authenticated admin
            $admin = auth()->user();
            
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'is_status' => 'required|in:ACTIVE,INACTIVE,SUSPENDED,PENDING,BLOCKED',
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users')->ignore($user->id),
                ],
            ]);
            
            // Store original values before update
            $oldValues = [
                'name' => $user->name,
                'email' => $user->email,
                'is_status' => $user->is_status,
            ];
            
            // Update user
            $user->update($validated);
            
            // Create audit log
            Logged::create([
                'user_id' => $user->id,
                'for' => 'USER_UPDATE',
                'message' => "User profile updated by Admin: {$admin->name} (ID: {$admin->id}, Email: {$admin->email}). Changes: " . $this->getChangeSummary($oldValues, $validated),
                'stack_trace' => json_encode([
                    'user_id' => $user->id,
                    'admin_name' => $admin->name,
                    'admin_id' => $admin->id,
                    'admin_email' => $admin->email,
                    'old_values' => $oldValues,
                    'new_values' => $validated,
                    'changes' => $this->getDetailedChanges($oldValues, $validated),
                    'updated_at' => now(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]),
                'from' => 'ADMIN_PANEL',
                'type' => 'SUCCESS',
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'User profile updated successfully',
                'user' => $user
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Log failed update attempt
            if (isset($user) && isset($admin)) {
                Logged::create([
                    'user_id' => $user->id ?? null,
                    'for' => 'USER_UPDATE_FAILED',
                    'message' => "Failed user update attempt by Admin: {$admin->name} (ID: {$admin->id}). Error: {$e->getMessage()}",
                    'stack_trace' => json_encode([
                        'error' => $e->getMessage(),
                        'admin_id' => $admin->id,
                        'admin_email' => $admin->email,
                        'failed_at' => now(),
                    ]),
                    'from' => 'ADMIN_PANEL',
                    'type' => 'ERROR',
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a summary of changes for the log message
     */
    private function getChangeSummary($oldValues, $newValues)
    {
        $changes = [];
        
        foreach ($newValues as $key => $newValue) {
            if ($oldValues[$key] != $newValue) {
                $changes[] = "{$key}: '{$oldValues[$key]}' → '{$newValue}'";
            }
        }
        
        return empty($changes) ? 'No changes detected' : implode(', ', $changes);
    }

    /**
     * Get detailed changes array
     */
    private function getDetailedChanges($oldValues, $newValues)
    {
        $changes = [];
        
        foreach ($newValues as $key => $newValue) {
            if ($oldValues[$key] != $newValue) {
                $changes[$key] = [
                    'from' => $oldValues[$key],
                    'to' => $newValue,
                ];
            }
        }
        
        return $changes;
    }
}
