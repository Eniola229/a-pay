<?php

namespace App\Http\Controllers\Loan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Balance;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\CreditLimit;
use App\Models\Borrow;
use App\Models\Transaction;


class CreditLimitController extends Controller
{
    public function view()
    {
        $user = Auth::user();
        $balance = Balance::where('user_id', $user->id)->first();
        $borrowHistory = Borrow::where('user_id', $user->id)->latest()->get();

        // Count and sum APay transactions
        $apayTransactions = Transaction::where('user_id', $user->id)->count();
        $totalTransactionAmount = Transaction::where('user_id', $user->id)->sum('amount');

        // Check for pending loan (but allow borrow if credit limit > 0)
        $pendingLoan = Borrow::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where(function($query) {
                $query->where('repayment_status', 'NOT PAID')
                      ->orWhere('repayment_status', 'NOT PAID FULL');
            })
            ->latest()
            ->first();

        // Check if credit limit already exists
        $existing = CreditLimit::where('user_id', $user->id)->first();

        // Skip limit calculation if already set
        if ($existing && $existing->limit_amount > 0) {
            return view('credit_limit', [
                'limit' => $existing->limit_amount,
                'hasLoan' => !!$pendingLoan,
                'loan' => $pendingLoan,
                'balance' => $balance,
                'borrowHistory' => $borrowHistory
            ]);
        }

        // Check minimum APay transaction requirement
        if ($apayTransactions < 5) {
            return view('credit_limit', [
                'requirementNotMet' => true,
                'requiredCount' => 5,
                'currentCount' => $apayTransactions,
                'balance' => $balance,
                'borrowHistory' => $borrowHistory
            ]);
        }

        // Begin credit limit calculation
        $limit = 0;

        // Base on balance
        if ($balance && $balance->balance > 0) {
            $limit += $balance->balance * 0.5;
        }

        // Reward transaction frequency
        if ($apayTransactions >= 5 && $apayTransactions < 20) {
            $limit += 1000;
        } elseif ($apayTransactions >= 20 && $apayTransactions < 50) {
            $limit += 2500;
        } elseif ($apayTransactions >= 50 || $totalTransactionAmount >= 50000) {
            $limit += 5000;
        }

        // Reduce based on past borrowings
        $previousBorrows = Borrow::where('user_id', $user->id)->sum('amount');
        $limit -= $previousBorrows * 0.3;

        // Clamp the credit limit
        $limit = max(0, min($limit, 5000));

        // Update or insert credit limit
        if ($existing) {
            $existing->update([
                'limit_amount' => $limit,
            ]);
        } else {
            CreditLimit::create([
                'id' => \Str::uuid(),
                'user_id' => $user->id,
                'limit_amount' => $limit,
            ]);
        }

        return view('credit_limit', [
            'limit' => $limit,
            'hasLoan' => !!$pendingLoan,
            'loan' => $pendingLoan,
            'balance' => $balance,
            'borrowHistory' => $borrowHistory
        ]);
    }


}
