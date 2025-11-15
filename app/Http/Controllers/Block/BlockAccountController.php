<?php

namespace App\Http\Controllers\Block;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class BlockAccountController extends Controller
{
   public function blockAccount(Request $request)
   {
        $request->validate([
            'email' => 'required|email',
            'mobile' => 'required|string',
        ]);

        // Find user by email and mobile
        $user = User::where('email', $request->email)
                    ->where('mobile', $request->mobile)
                    ->first();

        if (!$user) {
            return back()->withErrors(['email' => 'No account found with this email and mobile number.']);
        }

        // Update user status to BLOCKED
        $user->update([
            'is_status' => 'BLOCKED'
        ]);

        return back()->with('success', 'Your account has been successfully blocked.');
    }
}
