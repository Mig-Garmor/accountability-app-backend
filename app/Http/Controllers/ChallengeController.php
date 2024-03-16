<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Challenge;
use Illuminate\Http\Request;

class ChallengeController extends Controller
{
    public function createChallenge(Request $request)
    {
        $user = $request->user(); // Get the authenticated user
        $groupId = $request->input('groupId'); // Extract groupId from the request payload

        // Optional: Validate groupId to ensure it exists
        $group = Group::find($groupId);
        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        // Create a new challenge linked to the specified group
        $challenge = Challenge::create([
            'group_id' => $groupId,
            // Add other necessary challenge attributes here
        ]);

        // Link the user to the challenge (assuming you have a method to do this)
        $challenge->users()->attach($user->id);
        // This assumes you have a pivot table for linking challenges and users

        // Respond with the created challenge and a success message
        return response()->json([
            'message' => 'Challenge created successfully',
            'challenge' => $challenge,
            'status' => 200
        ], 201);
    }
}
