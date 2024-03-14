<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\User;

class GroupController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user(); // Get the authenticated user.

        // Create a new group.
        $group = Group::create();

        // Attach the user to the group with ADMIN permission.
        $group->users()->attach($user->id, ['permission' => 'ADMIN']);

        return response()->json(['message' => 'Group created successfully', 'group' => $group], 201);
    }
}