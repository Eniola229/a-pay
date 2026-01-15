<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function summary(Request $request)
    {
        $filter = $request->query('filter', 'all');
        $type = $request->query('type', 'all');
        
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
            $query->where('description', 'like', '%airtime%')
                  ->where('description', 'not like', '%Cashback%');
        } elseif ($type === 'data') {
            $query->where('description', 'like', '%data%')
                  ->where('description', 'not like', '%Cashback%');
        } elseif ($type === 'electricity') {
            $query->where('description', 'like', '%electricity%')
                  ->where('description', 'not like', '%Cashback%');
        } elseif ($type === 'betting') {
            $query->where('description', 'like', '%betting%')
                  ->where('description', 'not like', '%Cashback%');
        } elseif ($type === 'to_apay') {
            $query->where('reference', 'like', '%a-pay%');
        } elseif ($type === 'cashback') {
            $query->where('description', 'like', '%Cashback%');
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

    public function graphData(Request $request)
    {
        $service = $request->query('service', 'all');
        $filter = $request->query('filter', 'week');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        
        $query = Transaction::query();
        
        // Apply service type filter
        $serviceName = 'All Transactions';
        if ($service === 'wallet_topup') {
            $query->where('description', 'like', '%wallet top-up%');
            $serviceName = 'Wallet Top-up';
        } elseif ($service === 'airtime') {
            $query->where('description', 'like', '%airtime%')
                  ->where('description', 'not like', '%Cashback%');
            $serviceName = 'Airtime';
        } elseif ($service === 'data') {
            $query->where('description', 'like', '%data%')
                  ->where('description', 'not like', '%Cashback%');
            $serviceName = 'Data';
        } elseif ($service === 'electricity') {
            $query->where('description', 'like', '%electricity%')
                  ->where('description', 'not like', '%Cashback%');
            $serviceName = 'Electricity';
        } elseif ($service === 'betting') {
            $query->where('description', 'like', '%betting%')
                  ->where('description', 'not like', '%Cashback%');
            $serviceName = 'Betting';
        } elseif ($service === 'to_apay') {
            $query->where('reference', 'like', '%a-pay%');
            $serviceName = 'To A-Pay';
        } elseif ($service === 'cashback') {
            $query->where('description', 'like', '%Cashback%');
            $serviceName = 'Cashback';
        }
        
        // Determine date range and grouping
        $labels = [];
        $successAmounts = [];
        $pendingAmounts = [];
        $errorAmounts = [];
        $totalAmounts = [];
        $groupBy = 'day';
        
        if ($filter === 'custom' && $startDate && $endDate) {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
            $query->whereBetween('created_at', [$start, $end]);
            
            // Determine grouping based on date range
            $daysDiff = $start->diffInDays($end);
            if ($daysDiff > 60) {
                $groupBy = 'month';
            } elseif ($daysDiff > 14) {
                $groupBy = 'week';
            }
        } elseif ($filter === 'today') {
            $query->whereDate('created_at', Carbon::today());
            $groupBy = 'hour';
        } elseif ($filter === 'week') {
            $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
            $groupBy = 'day';
        } elseif ($filter === 'month') {
            $query->whereMonth('created_at', Carbon::now()->month)
                  ->whereYear('created_at', Carbon::now()->year);
            $groupBy = 'day';
        } elseif ($filter === 'all') {
            $groupBy = 'month';
        }
        
        // Group and aggregate data by status
        if ($groupBy === 'hour') {
            // For today - group by hour
            for ($i = 0; $i < 24; $i++) {
                $labels[] = $i . ':00';
                $successAmounts[] = 0;
                $pendingAmounts[] = 0;
                $errorAmounts[] = 0;
                $totalAmounts[] = 0;
            }
            
            $successResults = $query->clone()
                ->where('status', 'SUCCESS')
                ->select(
                    DB::raw('HOUR(created_at) as period'),
                    DB::raw('SUM(amount) as total')
                )
                ->groupBy('period')
                ->get();
            
            $pendingResults = $query->clone()
                ->where('status', 'PENDING')
                ->select(
                    DB::raw('HOUR(created_at) as period'),
                    DB::raw('SUM(amount) as total')
                )
                ->groupBy('period')
                ->get();
            
            $errorResults = $query->clone()
                ->where('status', 'ERROR')
                ->select(
                    DB::raw('HOUR(created_at) as period'),
                    DB::raw('SUM(amount) as total')
                )
                ->groupBy('period')
                ->get();
            
            foreach ($successResults as $result) {
                $successAmounts[$result->period] = floatval($result->total);
            }
            
            foreach ($pendingResults as $result) {
                $pendingAmounts[$result->period] = floatval($result->total);
            }
            
            foreach ($errorResults as $result) {
                $errorAmounts[$result->period] = floatval($result->total);
            }
            
            for ($i = 0; $i < 24; $i++) {
                $totalAmounts[$i] = $successAmounts[$i] + $pendingAmounts[$i] + $errorAmounts[$i];
            }
            
        } elseif ($groupBy === 'day') {
            // Get all dates in range
            $startDate = $filter === 'week' ? Carbon::now()->startOfWeek() : Carbon::now()->startOfMonth();
            $endDate = $filter === 'week' ? Carbon::now()->endOfWeek() : Carbon::now()->endOfMonth();
            
            if ($filter === 'custom' && $request->query('start_date') && $request->query('end_date')) {
                $startDate = Carbon::parse($request->query('start_date'));
                $endDate = Carbon::parse($request->query('end_date'));
            }
            
            $period = $startDate->copy();
            $dateMap = [];
            
            while ($period <= $endDate) {
                $dateKey = $period->format('Y-m-d');
                $labels[] = $period->format('M d');
                $dateMap[$dateKey] = count($labels) - 1;
                $successAmounts[] = 0;
                $pendingAmounts[] = 0;
                $errorAmounts[] = 0;
                $totalAmounts[] = 0;
                $period->addDay();
            }
            
            $successResults = $query->clone()
                ->where('status', 'SUCCESS')
                ->select(
                    DB::raw('DATE(created_at) as period'),
                    DB::raw('SUM(amount) as total')
                )
                ->groupBy('period')
                ->get();
            
            $pendingResults = $query->clone()
                ->where('status', 'PENDING')
                ->select(
                    DB::raw('DATE(created_at) as period'),
                    DB::raw('SUM(amount) as total')
                )
                ->groupBy('period')
                ->get();
            
            $errorResults = $query->clone()
                ->where('status', 'ERROR')
                ->select(
                    DB::raw('DATE(created_at) as period'),
                    DB::raw('SUM(amount) as total')
                )
                ->groupBy('period')
                ->get();
            
            foreach ($successResults as $result) {
                if (isset($dateMap[$result->period])) {
                    $index = $dateMap[$result->period];
                    $successAmounts[$index] = floatval($result->total);
                }
            }
            
            foreach ($pendingResults as $result) {
                if (isset($dateMap[$result->period])) {
                    $index = $dateMap[$result->period];
                    $pendingAmounts[$index] = floatval($result->total);
                }
            }
            
            foreach ($errorResults as $result) {
                if (isset($dateMap[$result->period])) {
                    $index = $dateMap[$result->period];
                    $errorAmounts[$index] = floatval($result->total);
                }
            }
            
            for ($i = 0; $i < count($labels); $i++) {
                $totalAmounts[$i] = $successAmounts[$i] + $pendingAmounts[$i] + $errorAmounts[$i];
            }
            
        } elseif ($groupBy === 'month') {
            $successResults = $query->clone()
                ->where('status', 'SUCCESS')
                ->select(
                    DB::raw('YEAR(created_at) as year'),
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('SUM(amount) as total')
                )
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get();
            
            $pendingResults = $query->clone()
                ->where('status', 'PENDING')
                ->select(
                    DB::raw('YEAR(created_at) as year'),
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('SUM(amount) as total')
                )
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get();
            
            $errorResults = $query->clone()
                ->where('status', 'ERROR')
                ->select(
                    DB::raw('YEAR(created_at) as year'),
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('SUM(amount) as total')
                )
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get();
            
            // Combine results
            $allPeriods = $successResults->concat($pendingResults)->concat($errorResults)
                ->unique(function($item) {
                    return $item->year . '-' . $item->month;
                })
                ->sortBy(function($item) {
                    return $item->year * 100 + $item->month;
                });
            
            $periodMap = [];
            foreach ($allPeriods as $period) {
                $key = $period->year . '-' . $period->month;
                $labels[] = Carbon::create($period->year, $period->month)->format('M Y');
                $periodMap[$key] = count($labels) - 1;
                $successAmounts[] = 0;
                $pendingAmounts[] = 0;
                $errorAmounts[] = 0;
                $totalAmounts[] = 0;
            }
            
            foreach ($successResults as $result) {
                $key = $result->year . '-' . $result->month;
                if (isset($periodMap[$key])) {
                    $index = $periodMap[$key];
                    $successAmounts[$index] = floatval($result->total);
                }
            }
            
            foreach ($pendingResults as $result) {
                $key = $result->year . '-' . $result->month;
                if (isset($periodMap[$key])) {
                    $index = $periodMap[$key];
                    $pendingAmounts[$index] = floatval($result->total);
                }
            }
            
            foreach ($errorResults as $result) {
                $key = $result->year . '-' . $result->month;
                if (isset($periodMap[$key])) {
                    $index = $periodMap[$key];
                    $errorAmounts[$index] = floatval($result->total);
                }
            }
            
            for ($i = 0; $i < count($labels); $i++) {
                $totalAmounts[$i] = $successAmounts[$i] + $pendingAmounts[$i] + $errorAmounts[$i];
            }
        }
        
        // Calculate statistics
        $successTransactions = $query->clone()->where('status', 'SUCCESS')->get();
        $pendingTransactions = $query->clone()->where('status', 'PENDING')->get();
        $errorTransactions = $query->clone()->where('status', 'ERROR')->get();
        
        // Type statistics
        $creditTransactions = $query->clone()->where('type', 'CREDIT')->get();
        $debitTransactions = $query->clone()->where('type', 'DEBIT')->get();
        $cashbackTransactions = $query->clone()->where('description', 'like', '%Cashback%')->get();
        
        $allTransactions = $query->clone()->get();
        
        $successTotal = $successTransactions->sum('amount');
        $pendingTotal = $pendingTransactions->sum('amount');
        $errorTotal = $errorTransactions->sum('amount');
        $totalAmount = $allTransactions->sum('amount');
        
        $creditTotal = $creditTransactions->sum('amount');
        $debitTotal = $debitTransactions->sum('amount');
        $cashbackTotal = $cashbackTransactions->sum('amount');
        
        $successCount = $successTransactions->count();
        $pendingCount = $pendingTransactions->count();
        $errorCount = $errorTransactions->count();
        $totalCount = $allTransactions->count();
        
        $creditCount = $creditTransactions->count();
        $debitCount = $debitTransactions->count();
        $cashbackCount = $cashbackTransactions->count();
        
        $average = $totalCount > 0 ? $totalAmount / $totalCount : 0;
        $successRate = $totalCount > 0 ? round(($successCount / $totalCount) * 100, 1) : 0;
        
        $stats = [
            'success_total' => $successTotal,
            'success_count' => $successCount,
            'pending_total' => $pendingTotal,
            'pending_count' => $pendingCount,
            'error_total' => $errorTotal,
            'error_count' => $errorCount,
            'total_amount' => $totalAmount,
            'total_count' => $totalCount,
            'credit_total' => $creditTotal,
            'credit_count' => $creditCount,
            'debit_total' => $debitTotal,
            'debit_count' => $debitCount,
            'cashback_total' => $cashbackTotal,
            'cashback_count' => $cashbackCount,
            'average' => $average,
            'success_rate' => $successRate
        ];
        
        return response()->json([
            'code' => 'success',
            'data' => [
                'labels' => $labels,
                'success_amounts' => $successAmounts,
                'pending_amounts' => $pendingAmounts,
                'error_amounts' => $errorAmounts,
                'total_amounts' => $totalAmounts,
                'service_name' => $serviceName,
                'stats' => $stats
            ]
        ]);
    }
}