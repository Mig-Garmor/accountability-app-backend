<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use App\Models\Group;
use App\Models\Challenge;
use Illuminate\Http\Request;
use App\Models\CompletedTask;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GroupController extends Controller
{
    public function storeGroup(Request $request)
    {
        $user = $request->user(); // Get the authenticated user.

        // Create a new group.
        $group = Group::create();

        // Attach the user to the group with ADMIN permission.
        $group->users()->attach($user->id, ['permission' => 'ADMIN']);

        return response()->json(['success' => true, 'message' => 'Group created successfully', 'group' => $group], 201);
    }
    public function getGroup(Request $request, $groupId)
    {
        // Load the group with all challenges, and eager load users for each challenge
        $group = Group::with(['challenges.users'])->find($groupId);

        // Check if the group was found
        if (!$group) {
            // Group not found, return a 404 response
            return response()->json(['message' => 'Group not found'], 404);
        }

        // Get the authenticated user's ID
        $userId = auth()->id();

        // Enhance each challenge with a 'userIsAssociated' attribute
        $activeChallenge = null; // Initialize the active challenge variable
        $group->challenges->each(function ($challenge) use ($userId, &$activeChallenge) {
            $challenge->userIsAssociated = $challenge->users->contains('id', $userId);
            // Optionally, unload the users relation if it's no longer needed in the response
            unset($challenge->users);

            // Determine the active challenge for the user
            if (!$activeChallenge && $challenge->userIsAssociated) {
                $activeChallenge = $challenge;
            }
        });

        // Directly query the group_user pivot table to find the permission for the given group_id and user_id
        $permission = DB::table('group_user')
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->value('permission'); // Fetch only the 'permission' column value

        // Return the group along with its enhanced challenges and the active challenge
        return response()->json([
            'group' => $group,
            'activeChallenge' => $activeChallenge, // Pass active challenge data as needed by the frontend
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

    public function removeUserFromGroup($groupId, $userIdToRemove)
    {

        DB::beginTransaction();

        try {
            Log::info('Group Id start', [$groupId]);
            $userIdFromUrl = (int) $userIdToRemove;
            $authUserId = auth()->id(); // Get the authenticated user's ID

            // If trying to remove another user, check for admin permissions
            if ($authUserId !== $userIdFromUrl) {
                DB::enableQueryLog();
                $permission = DB::table('group_user')
                    ->where('group_id', (int) $groupId)
                    ->where('user_id', $authUserId)
                    ->value('permission');

                $queryLog = DB::getQueryLog();
                Log::info('Query Log:', $queryLog);

                Log::info('Group Id', [(int) $groupId]);
                Log::info('User Id', [$authUserId]);
                Log::info('User to remove id', [$userIdFromUrl]);
                Log::info('User PERMISSION', [$permission]);


                if ($permission !== 'ADMIN') {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Unauthorized: You need admin privileges to remove another user.',
                        'success' => false
                    ], 403);
                }
            }

            $group = Group::findOrFail($groupId);

            // Detach the user from the group
            $group->users()->detach($userIdFromUrl);

            // If the user being removed is an admin, check if there are other admins left
            if ($authUserId === $userIdFromUrl || $permission === 'ADMIN') {
                $remainingAdmins = DB::table('group_user')
                    ->where('group_id', $groupId)
                    ->where('permission', 'ADMIN')
                    ->whereNotIn('user_id', [$userIdFromUrl])
                    ->exists();

                // If no remaining admins, assign the 'ADMIN' permission to the earliest joined member
                if (!$remainingAdmins) {
                    $newAdmin = $group->users()
                        ->whereNotIn('users.id', [$userIdFromUrl])
                        ->orderBy('created_at', 'asc')
                        ->first();

                    if ($newAdmin) {
                        $group->users()->updateExistingPivot($newAdmin->id, ['permission' => 'ADMIN']);
                    } else {
                        // This block is reached if the last admin leaves and no other members are present
                        // No action needed as there are no users to assign admin
                    }
                }
            }

            DB::commit();

            return response()->json(['message' => 'User successfully removed from the group and associated data cleaned up.', 'success' => true], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'An error occurred while removing the user from the group: ' . $e->getMessage(), 'success' => false], 500);
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
