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
        $type = $request->query('type', 'all'); // default = all types
        
        $query = Transaction::query();
        
        // Apply date filter
        if ($filter === 'today') {
            $query->whereDate('created_at', Carbon::today());
        } elseif ($filter === 'week') {
            $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        } elseif ($filter === 'month') {
            $query->whereMonth('created_at', Carbon::now()->month)
                  ->whereYear('created_at', Carbon::now()->year);
        }
        
        // Apply transaction type filter
        if ($type === 'wallet_topup') {
            $query->where('description', 'like', '%wallet top-up%');
        } elseif ($type === 'airtime') {
            $query->where('description', 'like', '%airtime%');
        } elseif ($type === 'data') {
            $query->where('description', 'like', '%data%');
        } elseif ($type === 'electricity') {
            $query->where('description', 'like', '%electricity%');
        } elseif ($type === 'betting') {
            $query->where('description', 'like', '%betting%');
        } elseif ($type === 'to_apay') {
            $query->where('reference', 'like', '%a-pay%');
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
