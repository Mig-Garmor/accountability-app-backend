<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
        ]);

        return response()->json(['user' => $user]);
    }

    public function allUsers()
    {
        $users = User::with(['groups'])->get()->map(function ($user) {
            $data = [
                'id' => $user->id,
                'name' => $user->name,
            ];

            // Check if user is associated with any group
            if ($user->groups->isNotEmpty()) {
                $data['groupId'] = $user->groups->first()->id; // Get the id of the first group
            }

            return $data;
        });

        return response()->json(['users' => $users]);
    }

    public function currentUser()
    {
        // Retrieve the currently authenticated user with the related groups
        $user = Auth::user()->load('groups');

        // Check if a user is authenticated
        if (!$user) {
            // If no user is currently authenticated, you might want to return an appropriate response
            return response()->json(['message' => 'No authenticated user'], 404);
        }

        // Prepare the response data including user details
        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];

        // Check if user is associated with any groups and add the first group's ID to the response
        if ($user->groups->isNotEmpty()) {
            $data['groupId'] = $user->groups->first()->id;
        }

        // Return the authenticated user's information along with the group ID if available
        return response()->json($data);
    }
}
