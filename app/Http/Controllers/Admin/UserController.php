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


class UserController extends Controller
{
    public function users(Request $request)
    {
        $users = User::with('balance')->orderBy('created_at', 'desc')->paginate(20);
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
            
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'is_status' => 'required|in:ACTIVE,INACTIVE,SUSPENDED,PENDING,BLOCKED',
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users')->ignore($user->id),
                ],
                'mobile' => [
                    'required',
                    'string',
                    'max:20',
                    Rule::unique('users')->ignore($user->id),
                ],
            ]);
            
            $user->update($validated);
            
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
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}
