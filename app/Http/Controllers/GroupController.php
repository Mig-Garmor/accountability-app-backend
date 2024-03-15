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
        // Attempt to find the group by its ID
        $group = Group::find($groupId);

        // Check if the group was found
        if (!$group) {
            // Group not found, return a 404 response
            return response()->json(['message' => 'Group not found'], 404);
        }

        // Return the group if found
        return response()->json($group);
    }
}
