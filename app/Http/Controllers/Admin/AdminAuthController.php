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
        $transactions = Transaction::with('user')->orderBy('created_at', 'desc')->paginate(20);
        return view('admin.transactions', compact('transactions'));
    }

     public function users(Request $request)
    {
        $users = User::with('balance')->orderBy('created_at', 'desc')->paginate(20);
        return view('admin.users', compact('users'));
    }
     public function complians(Request $request)
    {
        $complians = ContactInquiry::orderBy('created_at', 'desc')->paginate(20);
        return view('admin.complians', compact('complians'));
    }

    public function errors(Request $request)
    {
        $errors = Errors::orderBy('created_at', 'desc')->paginate(20);
        return view('admin.errors', compact('errors'));
    }

    public function updateTransaction(Request $request)
    {
        $transaction = Transaction::find($request->id);

        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        $oldStatus = $transaction->status;
        $transaction->description = $request->description;
        $transaction->status = $request->status;
        $transaction->save();

        // If status is changed from "PENDING" to "ERROR", refund the user
        if ($oldStatus === 'PENDING' && $request->status === 'ERROR') {
            $user = User::find($request->user_id);
            $balance = Balance::where('user_id', $user->id)->first();
            if ($user) {
                $balance->balance += $transaction->amount;
                $balance->save();
                Transaction::create([
                    'user_id'     => $user->id,
                    'amount'      => $transaction->amount,
                    'description' => "Your funds have been refunded.",
                    'status'      => 'SUCCESS'
                ]);
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
        ]);

        $user->update([
            'name' => $request->name,
            'mobile' => $request->mobile,
            'email' => $request->email,
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
