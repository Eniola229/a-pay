<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use App\Models\Balance;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
        public function view(Request $request)
        {
            $user = auth()->user();
            $balance = Balance::where('user_id', $user->id)->first();
            return view('profile', compact('balance'));
        }

        public function updateProfile(Request $request)
        {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'mobile' => [
                    'required',
                    'string',
                    'max:15',
                    'regex:/^\+234[0-9]{10}$/',
                    'unique:users,mobile,' . $user->id
                ],
                'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user->name = $request->name;
            $user->mobile = $request->mobile;
            $user->email = $request->email;

            $user->save();

            return response()->json(['success' => 'Profile updated successfully.']);
        }


        public function updatePassword(Request $request)
        {
            $validator = Validator::make($request->all(), [
                'old_password' => 'required',
                'new_password' => 
                    'required',
                    'confirmed',
                    'min:8',
                    'not_in:123456789,password,12345678,123123,qwerty', // Add common passwords to reject
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/' // Enforce complexity
                ,
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user = Auth::user();

            if (!Hash::check($request->old_password, $user->password)) {
                return response()->json(['message' => 'Current password is incorrect.'], 422);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            return response()->json(['success' => 'Password updated successfully.']);
        }


    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
