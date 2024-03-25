<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\User;

class GroupController extends Controller
{
    public function storeGroup(Request $request)
    {
        $user = $request->user(); // Get the authenticated user.

        // Create a new group.
        $group = Group::create();

        // Attach the user to the group with ADMIN permission.
        $group->users()->attach($user->id, ['permission' => 'ADMIN']);

        return response()->json(['message' => 'Group created successfully', 'group' => $group], 201);
    }
    public function getGroup(Request $request, $groupId)
    {
        // Attempt to find the group by its ID with challenges
        $group = Group::with('challenges')->find($groupId);

        // Check if the group was found
        if (!$group) {
            // Group not found, return a 404 response
            return response()->json(['message' => 'Group not found'], 404);
        }

        // Return the group along with its challenges
        return response()->json($group);
    }

    public function getActiveChallenge(Request $request, $groupId)
    {
        // Retrieve the group with all its challenges
        $group = Group::with('challenges')->find($groupId);

        // Check if the group was found
        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        // Retrieve the current user's ID
        // Assuming you are using Laravel's default authentication
        $userId = auth()->id();

        // Filter through the group's challenges to find the first one associated with the user
        $activeChallenge = $group->challenges->first(function ($challenge) use ($userId) {
            return $challenge->users->contains($userId);
        });

        // Check if an active challenge was found for the user
        if (!$activeChallenge) {
            return response()->json(['message' => 'There are no active challenges for this group'], 404);
        }

        // Return the active challenge
        return response()->json($activeChallenge, 200);
    }
}
