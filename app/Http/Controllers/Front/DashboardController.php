<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\Balance;
use App\Models\GeneralNotification;
use Illuminate\Support\Facades\Hash;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();
        $notifications = GeneralNotification::orderBy('created_at', 'desc')->get();
        $balance = Balance::where('user_id', $user->id)->first();
        return view('dashboard', compact('balance', 'notifications'));
    }

    public function setPin(Request $request)
    {
        $request->validate([
            'pin' => 'required|digits:4',
            'confirm_pin' => 'required|same:pin'
        ]);

        $user = Auth::user();

        $balance = Balance::where('user_id', $user->id)->first();

        if ($balance) {
            // Only update the PIN, do not modify the balance
            $balance->update([
                'pin' => Hash::make($request->pin),
            ]);
        } else {
            // Create a new record with balance and PIN
            Balance::create([
                'user_id' => $user->id,
                'balance' => 0.00,
                'pin' => Hash::make($request->pin),
            ]);
        }

        return response()->json(['success' => 'PIN set successfully!']);
    }

    public function resetPin(Request $request)
    {
        $request->validate([
            'current_pin' => 'required|min:4',
            'new_pin' => 'required|min:4|max:4',
            'confirm_pin' => 'required|same:new_pin',
        ]);

        $user = Auth::user();
        $balance = Balance::where('user_id', $user->id)->first();

        if (!Hash::check($request->current_pin, $user->password)) {
            return response()->json(['message' => 'Password is incorrect'], 400);
        }

        $balance->pin = Hash::make($request->new_pin);
        $balance->save();

        return response()->json(['success' => 'PIN reset successfully']);
    }
}
