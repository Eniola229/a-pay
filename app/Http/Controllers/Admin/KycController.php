<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KycProfile;
use Illuminate\Http\Request;
use Carbon\Carbon;

class KycController extends Controller
{
    public function summary(Request $request)
    {
        $query = KycProfile::query();

        // Only filter by status if provided
        if ($request->filled('status') && in_array($request->status, ['accepted', 'rejected', 'pending'])) {
            $query->where('status', strtoupper($request->status));
        }

        // Only filter by today if requested
        if ($request->filled('filter') && $request->filter === 'today') {
            $query->whereDate('created_at', Carbon::today());
        }

        $totalKyc = $query->count();

        return response()->json([
            'code' => 'success',
            'data' => [
                'total_kyc' => $totalKyc
            ]
        ]);
    }
}
