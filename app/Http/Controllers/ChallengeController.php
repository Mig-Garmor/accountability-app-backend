<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Challenge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class ChallengeController extends Controller
{
    public function createChallenge(Request $request)
    {
        $user = $request->user(); // Get the authenticated user
        $data = $request->only('groupId', 'startDate'); // Extract groupId and startDate from the request payload

        // Validate groupId and startDate
        $validator = Validator::make($data, [
            'groupId' => 'required|exists:groups,id',
            'startDate' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 400);
        }

        // Find the group using groupId
        $group = Group::find($data['groupId']);
        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        // Create a new challenge linked to the specified group and with startDate
        $challenge = Challenge::create([
            'group_id' => $data['groupId'],
            'start_date' => $data['startDate'], // Ensure your Challenge model and database schema support this field
            // Add other necessary challenge attributes here
        ]);

        // Link the user to the challenge (assuming you have a method to do this)
        $challenge->users()->attach($user->id); // This assumes you have a pivot table for linking challenges and users

        // Respond with the created challenge, including startDate, and a success message
        return response()->json([
            'message' => 'Challenge created successfully',
            'challenge' => $challenge,
        ], 201);
    }
}
