<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use App\Models\Group;
use App\Models\Challenge;
use Illuminate\Http\Request;
use App\Models\CompletedTask;
use Illuminate\Support\Facades\DB;

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
        // Attempt to find the group by its ID and load challenges
        $group = Group::with(['challenges.users' => function ($query) {
            $query->where('users.id', auth()->id());
        }])->find($groupId);

        // Check if the group was found
        if (!$group) {
            // Group not found, return a 404 response
            return response()->json(['message' => 'Group not found'], 404);
        }

        // Find the first active challenge for the user within this group
        $activeChallenge = $group->challenges->first(function ($challenge) {
            return $challenge->users->contains('id', auth()->id());
        });

        // Format the active challenge to return only relevant details or null if no active challenge
        $activeChallengeData = $activeChallenge ? true : false;

        $userId = auth()->id(); // Retrieve the authenticated user's ID

        // Directly query the group_user pivot table to find the permission for the given group_id and user_id
        $permission = DB::table('group_user')
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->value('permission'); // Fetch only the 'permission' column value



        // Return the group along with its challenges and the active challenge if any
        return response()->json([
            'group' => $group,
            'activeChallenge' => $activeChallengeData,
            'userPermission' => $permission
        ]);
    }


    public function getActiveChallenge(Request $request, $groupId)
    {
        // Retrieve the current user's ID
        $userId = auth()->id();

        // Retrieve the group with challenges and also load the pivot data (permissions) for the authenticated user
        $group = Group::with(['challenges.users.tasks.completedTasks', 'users' => function ($query) use ($userId) {
            $query->where('users.id', $userId)->withPivot('permission');
        }])->find($groupId);

        // Check if the group was found
        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        // Access the user's permission for this group from the pivot
        $userPermission = optional($group->users->first())->pivot->permission ?? null;

        // If for some reason we don't have a permission (user not part of the group), you might handle it differently
        if (!$userPermission) {
            return response()->json(['message' => 'User does not have permissions for this group'], 403);
        }

        // Filter through the group's challenges to find the first one associated with the user
        $activeChallenge = $group->challenges->first(function ($challenge) use ($userId) {
            return $challenge->users->contains('id', $userId);
        });

        // Check if an active challenge was found for the user
        if (!$activeChallenge) {
            return response()->json(['message' => 'There are no active challenges for this user'], 404);
        }

        // You might want to include userPermission in the response if it's needed on the front end
        $activeChallenge['userPermission'] = $userPermission;

        // Return the active challenge along with user permission
        return response()->json($activeChallenge, 200);
    }

    public function removeUserFromGroup($groupId, $userId)
    {
        DB::beginTransaction();

        try {
            $group = Group::findOrFail($groupId);

            // Step 2: Detach the user from the group
            $group->users()->detach($userId);

            // Step 3: Detach the user from challenges and collect challenge IDs
            $challengeIds = $group->challenges()->pluck('challenges.id');
            Challenge::whereIn('id', $challengeIds)->each(function ($challenge) use ($userId) {
                $challenge->users()->detach($userId);
            });

            // Step 4: Delete all tasks associated with the user and these challenges
            $taskIds = Task::where('user_id', $userId)
                ->whereIn('challenge_id', $challengeIds)
                ->pluck('id');

            Task::whereIn('id', $taskIds)->delete();

            // Step 5: Delete all completed tasks associated with these tasks
            CompletedTask::whereIn('task_id', $taskIds)->delete();

            DB::commit();

            return response()->json(['message' => 'User successfully removed from the group and associated data cleaned up.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'An error occurred while removing the user from the group: ' . $e->getMessage()], 500);
        }
    }

    public function getAllGroups()
    {
        $groups = Group::withCount(['users', 'challenges'])
            ->get(['id', 'name'])
            ->map(function ($group) {
                return [
                    'groupId' => $group->id,
                    'numberOfMembers' => $group->users_count,
                    'numberOfChallenges' => $group->challenges_count,
                ];
            });

        return response()->json(['message' => 'Groups fetched successfully.', 'data' => $groups]);
    }
}
