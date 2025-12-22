<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TransactionController extends Controller
{
    public function summary(Request $request)
    {
        $filter = $request->query('filter', 'all'); // default = all

        $query = Transaction::query();

        if ($filter === 'today') {
            $query->whereDate('created_at', Carbon::today());
        } elseif ($filter === 'week') {
            $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        } elseif ($filter === 'month') {
            $query->whereMonth('created_at', Carbon::now()->month)
                  ->whereYear('created_at', Carbon::now()->year);
        }

        $totalAmount = $query->sum('amount');
        $totalTransactions = $query->count();

        return response()->json([
            'code' => 'success',
            'data' => [
                'total_transactions' => $totalTransactions,
                'total_amount' => $totalAmount
            ]
        ]);
    }
}
