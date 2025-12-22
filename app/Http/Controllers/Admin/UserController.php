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

        //fetch user kyc
        $kyc = KycProfile::where('user_id', $user->id)->first();

        return view('admin.user-details', compact('user', 'balance', 'transactions', 'loans', 'kyc'));
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
}
