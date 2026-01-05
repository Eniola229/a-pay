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
use App\Models\Logged;

class AdminAuthController extends Controller
{
    /**
     * Show the login page.
     *
     * @return View
     */
    public function index(): View
    {
        return view('admin.login');
    }  

    /**
     * Show the registration page.
     *
     * @return View
     */
    public function registration(): View
    {
        return view('admin.registration');
    }

    /**
     * Handle login request.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function postLogin(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');
        
        // Use the 'admins' guard for authentication
        if (Auth::guard('admin')->attempt($credentials)) {
            return redirect()->intended('admin/dashboard')
                        ->withSuccess('You have successfully logged in');
        }

        return redirect("admin/login")->with('error', 'Oops! You have entered invalid credentials');
    }

    /**
     * Handle registration request.
     *
     * @param Request $request
     * @return RedirectResponse
     */
        public function postRegistration(Request $request): RedirectResponse
        {  
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:admins',
                'mobile' => 'required|numeric|unique:admins',
                'password' => 'required|min:6',
            ]);

            // Hash the password before saving to the database
            $data = $request->all();
            $data['password'] = bcrypt($data['password']);

            // Use updateOrCreate to either update or create a new admin record
            $admin = Admin::updateOrCreate(
                [
                    'email' => $data['email'], // Search by email
                ],
                [
                    'name' => $data['name'],
                    'mobile' => $data['mobile'],
                    'password' => $data['password'],
                ]
            );

            return redirect()->back()->with('message', 'Great! The admin record has been successfully updated or created.');
        }
    /**
     * Show the admin dashboard.
     *
     * @return View|RedirectResponse
     */
    public function dashboard()
    {
        $users = User::orderBy('created_at', 'desc')->get();
        $tranCount = Transaction::all()->count();
        $userCount = User::all()->count();
        $totalBalance = Balance::all()->sum('balance');

        return view('admin.dashboard', compact("users", "userCount", "tranCount", 'totalBalance'));
    }

    public function getBalance()
    {
        $token = env('EBILLS_API_TOKEN');

        $response = Http::withToken($token)
            ->get('https://ebills.africa/wp-json/api/v2/balance');

        if ($response->successful()) {
            return response()->json($response->json());
        } else {
            return response()->json([
                'code' => 'error',
                'message' => 'Failed to fetch balance',
                'details' => $response->body()
            ], $response->status());
        }
    }
 
public function transactions(Request $request)
{
    $query = Transaction::with('user');
    
    // Apply date filters
    if ($request->year) {
        $query->whereYear('created_at', $request->year);
    }
    if ($request->month) {
        $query->whereMonth('created_at', $request->month);
    }
    if ($request->day) {
        $query->whereDay('created_at', $request->day);
    }
    if ($request->from && $request->to) {
        $query->whereBetween('created_at', [$request->from, $request->to]);
    }
    
    // ENHANCED SEARCH - This is the only change you need
    if ($request->search) {
        $searchTerm = $request->search;
        
        $query->where(function ($q) use ($searchTerm) {
            $q->where('description', 'like', "%{$searchTerm}%")
              ->orWhere('reference', 'like', "%{$searchTerm}%")
              ->orWhere('beneficiary', 'like', "%{$searchTerm}%")
              ->orWhere('source', 'like', "%{$searchTerm}%")
              ->orWhere('type', 'like', "%{$searchTerm}%")
              ->orWhere('status', 'like', "%{$searchTerm}%")
              ->orWhere('amount', 'like', "%{$searchTerm}%")
              ->orWhereHas('user', function($userQuery) use ($searchTerm) {
                  $userQuery->where('name', 'like', "%{$searchTerm}%")
                           ->orWhere('email', 'like', "%{$searchTerm}%")
                           ->orWhere('mobile', 'like', "%{$searchTerm}%");
              });
        });
    }
    
    $transactions = $query->orderByDesc('created_at')->paginate(20);
    
    // Rest of your code stays the same...
    $summary = [
        'total' => (clone $query)->sum('amount'),
        'success' => (clone $query)->where('status', 'SUCCESS')->sum('amount'),
        'failed' => (clone $query)->where('status', 'ERROR')->sum('amount'),
        'wallet_topup' => (clone $query)->where('description', 'like', '%wallet top-up%')->sum('amount'),
        'to_apay' => (clone $query)->where('reference', 'like', '%a-pay%')->sum('amount'),
        'airtime' => (clone $query)->where('description', 'like', '%airtime%')->sum('amount'),
        'data' => (clone $query)->where('description', 'like', '%data%')->sum('amount'),
        'electricity' => (clone $query)->where('description', 'like', '%electricity%')->sum('amount'),
        'betting' => (clone $query)->where('description', 'like', '%betting%')->sum('amount'),
    ];
    
    return view('admin.transactions', compact('transactions', 'summary'));
}

     public function complians(Request $request)
    {
        $complians = ContactInquiry::orderBy('created_at', 'desc')->paginate(20);
        return view('admin.complians', compact('complians'));
    }

    public function errors(Request $request)
    {
        $logs = Logged::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('admin.errors', compact('logs'));
    }

     public function updateTransaction(Request $request)
    {
        $transaction = Transaction::find($request->id);
        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        // Prevent editing SUCCESS transactions
        if ($transaction->status === 'SUCCESS') {
            return response()->json(['message' => 'Cannot edit successful transactions'], 403);
        }

        // Check if transaction has already been edited by admin
        if ($transaction->admin_edited) {
            return response()->json([
                'message' => 'This transaction has already been edited by an admin and cannot be modified again. ' .
                            'Edited by Admin ID: ' . $transaction->edited_by_admin_id . ' on ' . 
                            $transaction->edited_at->format('d M Y, h:i A')
            ], 403);
        }

        $oldStatus = $request->old_status;
        $newStatus = $request->status;
        $statusChanged = $oldStatus !== $newStatus;

        // Get the currently logged-in admin
        $admin = Auth::guard('admin')->user();
        
        if (!$admin) {
            return response()->json(['message' => 'Admin not authenticated'], 401);
        }

        // Verify admin password if status is being changed
        if ($statusChanged) {
            if (!Hash::check($request->admin_password, $admin->password)) {
                return response()->json(['message' => 'Invalid admin password'], 403);
            }
        }

        // Update transaction and mark as edited by admin
        $transaction->description = $request->description;
        $transaction->status = $newStatus;
        $transaction->admin_edited = true;
        $transaction->edited_by_admin_id = $admin->id;
        $transaction->edited_at = now();
        $transaction->save();

        // Log the update with full admin details
        Logged::create([
            'user_id' => $request->user_id,
            'for' => 'TRANSACTION_UPDATE',
            'message' => "Transaction edited by Admin: {$admin->name} (ID: {$admin->id}, Email: {$admin->email}). Status changed from {$oldStatus} to {$newStatus}",
            'stack_trace' => json_encode([
                'transaction_id' => $transaction->id,
                'admin_name' => $admin->name,
                'admin_id' => $admin->id,
                'admin_email' => $admin->email,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'old_description' => $transaction->getOriginal('description'),
                'new_description' => $transaction->description,
                'edited_at' => now()
            ]),
            't_reference' => $transaction->reference,
            'from' => 'ADMIN_PANEL',
            'type' => 'SUCCESS',
        ]);

        // Process refund if checkbox is checked
        if ($request->process_refund && $newStatus === 'ERROR') {
            $user = User::find($request->user_id);
            
            if ($user && $transaction->description !== 'Wallet Top-up') {
                $balance = Balance::where('user_id', $user->id)->first();
                
                if ($balance) {
                    $balanceBefore = $balance->balance;
                    
                    // Use the protected method instead of direct update
                    $balance->incrementBalance($transaction->amount);
                    
                    $balanceAfter = $balance->fresh()->balance; // Get the updated balance
                    
                    // Create refund transaction
                    Transaction::create([
                        'user_id' => $user->id,
                        'amount' => $transaction->amount,
                        'beneficiary' => $user->mobile ?? $user->email ?? 'System Refund',
                        'description' => "Refund for failed transaction: " . $transaction->description,
                        'status' => 'SUCCESS',
                        'type' => 'CREDIT',
                        'reference' => 'REF-' . now()->format('YmdHis') . '-' . $user->id,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $balanceAfter,
                        'charges' => 0,
                        'cash_back' => 0,
                    ]);

                    // Log refund with admin details
                    Logged::create([
                        'user_id' => $user->id,
                        'for' => 'REFUND',
                        'message' => "Refund processed by Admin: {$admin->name} (ID: {$admin->id}, Email: {$admin->email}) for failed transaction",
                        'stack_trace' => json_encode([
                            'original_transaction_id' => $transaction->id,
                            'refund_amount' => $transaction->amount,
                            'admin_name' => $admin->name,
                            'admin_id' => $admin->id,
                            'admin_email' => $admin->email,
                            'balance_before' => $balanceBefore,
                            'balance_after' => $balanceAfter
                        ]),
                        't_reference' => $transaction->reference,
                        'from' => 'ADMIN_PANEL',
                        'type' => 'SUCCESS',
                    ]);

                    return response()->json(['message' => 'Transaction updated and refund processed successfully']);
                }
            }
        }
        return response()->json(['message' => 'Transaction updated successfully']);
    }

   // Fetch user details for editing
    public function Useredit($id)
    {
        $user = User::with('balance')->findOrFail($id);
        return response()->json($user);
    }

    // Update user details (excluding balance)
    public function Userupdate(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'mobile' => 'required|string|max:15',
            'email' => 'required|email|max:255|unique:users,email,' . $id,
            'status' => 'required|string',
        ]);

        $user->update([
            'name' => $request->name,
            'mobile' => $request->mobile,
            'email' => $request->email,
            'is_status' => $request->status,
        ]);

        return response()->json(['success' => true, 'message' => 'Customer details updated successfully.']);
    }
    public function notification(Request $request)
    {
        $notifications = GeneralNotification::orderBy('created_at', 'desc')->paginate(20);
        return view('admin.notification', compact('notifications'));
    }

    public function Notificationstore(Request $request)
    {
        $notification = GeneralNotification::create([
            'title' => $request->title,
            'details' => $request->details,
            'expiry_date' => $request->expiry_date,
            'links' => $request->links,
        ]);

        return response()->json([
            'success' => true,
            'notification' => $notification
        ]);
    }

    public function Notificationdestroy($id)
    {
        $notification = GeneralNotification::find($id);
        
        if ($notification) {
            $notification->delete();
            return response()->json(['success' => true]);
        }
        
        return response()->json(['success' => false]);
    }

    /**
     * Handle admin logout.
     *
     * @return RedirectResponse
     */
    public function logout(): RedirectResponse
    {
        Session::flush();
        Auth::guard('admin')->logout();

        return redirect('admin/login');
    }

    public function loans()
    {
        $loans = Borrow::with('user.balance')
            ->latest()
            ->paginate(15);

        return view('admin.loans', compact('loans'));
    }

}
