<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Challenge;
use App\Models\GroupUser;
use Illuminate\Http\Request;
use App\Models\ChallengeUser;
use Illuminate\Support\Facades\Auth;
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

    public function enterChallenge(Request $request)
    {
        // Retrieve the current authenticated user's ID
        $userId = Auth::id();

        // Ensure a challenge ID is provided in the request
        if (!$request->has('challengeId')) {
            return response()->json(['message' => 'No challenge specified'], 400);
        }
        $challengeId = $request->input('challengeId');

        // Check if the user is part of any group
        $isInGroup = GroupUser::where('user_id', $userId)->exists();

        if (!$isInGroup) {
            // User is not in any group, return an appropriate response
            return response()->json(['message' => 'User must be part of a group to enter a challenge'], 403);
        }

        // Check if the user is already part of the challenge
        $alreadyInChallenge = ChallengeUser::where('user_id', $userId)
            ->where('challenge_id', $challengeId)
            ->exists();

        if ($alreadyInChallenge) {
            // User is already in the challenge, return an appropriate response
            return response()->json(['message' => 'User is already part of this challenge'], 409);
        }

        // Add the user to the challenge
        ChallengeUser::create([
            'user_id' => $userId,
            'challenge_id' => $challengeId,
        ]);

        // Return a success response
        return response()->json(['message' => 'User entered the challenge successfully']);
    }
}
