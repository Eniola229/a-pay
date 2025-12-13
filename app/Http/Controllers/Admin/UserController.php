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

        return view('admin.user-details', compact('user', 'balance', 'transactions', 'loans'));
    }
}
