<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Challenge;
use App\Models\GroupUser;
use Illuminate\Http\Request;
use App\Models\ChallengeUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;


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
            return response()->json(['message' => $validator->errors(), 'success' => false], 400);
        }

        // Find the group using groupId
        $group = Group::find($data['groupId']);
        if (!$group) {
            return response()->json(['message' => 'Group not found', 'success' => false], 404);
        }

        // Check if the user has ADMIN permission for this group
        $groupUser = DB::table('group_user')
            ->where('group_id', $data['groupId'])
            ->where('user_id', $user->id)
            ->first();

        if (!$groupUser || $groupUser->permission !== 'ADMIN') {
            return response()->json(['message' => 'Unauthorized: You do not have permission to create a challenge', 'success' => false], 403);
        }

        // Create a new challenge linked to the specified group and with startDate
        $challenge = Challenge::create([
            'group_id' => $data['groupId'],
            'start_date' => $data['startDate'], // Ensure your Challenge model and database schema support this field
            // Add other necessary challenge attributes here
        ]);

        // Respond with the created challenge, including startDate, and a success message
        return response()->json([
            'message' => 'Challenge created successfully',
            'challenge' => $challenge,
            'success' => true
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

        // Fetch the challenge with its associated group
        $challenge = Challenge::with('group')->find($challengeId);
        if (!$challenge) {
            return response()->json(['message' => 'Challenge not found'], 404);
        }

        // Check if the user is part of the group associated with the challenge
        $isInGroup = GroupUser::where('user_id', $userId)
            ->where('group_id', $challenge->group_id)
            ->exists();

        if (!$isInGroup) {
            return response()->json(['message' => 'User must be part of the group associated with this challenge to enter'], 403);
        }

        // Fetch all current challenges of the user that are active
        $activeChallenges = ChallengeUser::where('user_id', $userId)
            ->join('challenges', 'challenge_user.challenge_id', '=', 'challenges.id')
            ->where('challenges.start_date', '>', now()->subDays(28)) // Ensuring it's within the 28-day active period
            ->orderBy('challenges.start_date', 'asc')
            ->get(['challenges.id', 'challenges.start_date']);

        // Check active challenge constraints
        if ($activeChallenges->count() >= 2) {
            return response()->json(['message' => 'You cannot be part of more than two active challenges'], 409);
        }

        if ($activeChallenges->count() == 1) {
            $firstChallenge = $activeChallenges->first();
            $firstChallengeEndDate = $firstChallenge->start_date->copy()->addDays(28);
            $last14DaysMark = $firstChallengeEndDate->subDays(14);
            if ($challenge->start_date < $last14DaysMark) {
                return response()->json(['message' => 'The second challenge can only be entered if there are less than 14 days remaining in the first challenge'], 409);
            }
        }

        // Check if the user is already part of the challenge they want to enter
        $alreadyInChallenge = ChallengeUser::where('user_id', $userId)
            ->where('challenge_id', $challengeId)
            ->exists();
        if ($alreadyInChallenge) {
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



    protected function getUserPermission($userId, $groupId)
    {
        return DB::table('group_user')
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->value('permission');  // Fetch only the 'permission' column value
    }

    public function deleteChallenge(Request $request, $challengeId)
    {
        $user = $request->user(); // Get the authenticated user

        // Find the challenge using the $challengeId parameter from the route
        $challenge = Challenge::find($challengeId);

        if (!$challenge) {
            return response()->json(['message' => 'Challenge not found', 'success' => false], 404);
        }

        $groupId = $challenge->group_id;

        // Get the user permission for the specific group
        $permission = $this->getUserPermission($user->id, $groupId);

        // Authorization check: Ensure the user requesting deletion has 'ADMIN' permission
        if ($permission !== 'ADMIN') {
            return response()->json(['message' => 'Unauthorized to delete this challenge', 'success' => false], 403);
        }

        // Delete the challenge
        try {
            $challenge->delete();
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete the challenge', 'success' => false], 500);
        }

        return response()->json(['message' => 'Challenge deleted successfully', 'success' => true]);
    }
}
