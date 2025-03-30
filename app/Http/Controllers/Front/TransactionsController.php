<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\Balance;
use App\Models\Transaction;
use Illuminate\Support\Facades\Hash;

class TransactionsController extends Controller
{
    public function view(Request $request)
    {
        $transactions = Transaction::where('user_id', Auth::user()->id)->orderBy('created_at', 'desc')->get();
        return view('transactions', compact('transactions'));
    }

}
