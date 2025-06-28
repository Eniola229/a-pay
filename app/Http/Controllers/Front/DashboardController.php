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
use Illuminate\Support\Facades\Log;

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
        try {
            $request->validate([
                'pin' => 'required|digits:4',
                'confirm_pin' => 'required|same:pin'
            ]);

            $user = Auth::user();

            $balance = Balance::where('user_id', $user->id)->first();

            if ($balance) {
                // Only update the PIN
                $balance->update([
                    'pin' => Hash::make($request->pin),
                ]);
            } else {
                // Create a new balance record
                Balance::create([
                    'user_id' => $user->id,
                    'balance' => 0.00,
                    'owe' => 0.00,
                    'pin' => Hash::make($request->pin),
                ]);
            }

            return response()->json(['success' => 'PIN set successfully!']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('PIN validation failed', ['errors' => $e->errors(), 'user_id' => Auth::id()]);
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Error setting PIN', ['message' => $e->getMessage(), 'user_id' => Auth::id()]);
            return response()->json(['error' => 'An unexpected error occurred. Please try again.'], 500);
        }
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
