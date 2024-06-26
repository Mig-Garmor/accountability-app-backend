<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // Create a new token for the user
            $token = $user->createToken('YourAppNameTokenName')->plainTextToken;

            // Retrieve the first group ID the user belongs to, if any
            $groupId = $user->groups()->first()?->id;

            return response()->json([
                'token' => $token,
                'group_id' => $groupId, // This will be null if the user does not belong to any group
            ]);
        }



        return response()->json(['error' => 'The provided credentials do not match our records.'], 401);
    }

    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request...
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout successful', 'status' => 200]);
    }
}
