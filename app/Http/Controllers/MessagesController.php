<?php

namespace App\Http\Controllers;

use App\Models\GroupUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User; // Assuming you have a User model
use App\Models\Group; // Assuming you have a Group model
use App\Models\Message; // Ensure you have the Message model created


class MessagesController extends Controller
{
    /**
     * Handle the invitation of a user to a group.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function inviteUser(Request $request)
    {
        $user = $request->user();
        $data = $request->only('targetUserId', 'groupId'); // Extract groupId and startDate from the request payload

        // Validate the incoming request data
        $validator = Validator::make($data, [
            'targetUserId' => 'required|exists:users,id',
            'groupId' => 'required|exists:groups,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => ['message' => 'Validation errors', 'details' => $validator->errors()]], 400);
        }

        // Retrieve the validated input data
        $targetUserId = $data['targetUserId'];
        $groupId = $data['groupId'];

        // Check if an invite message already exists for this user and group
        $inviteExists = Message::where('target_user_id', $targetUserId)
            ->where('group_id', $groupId)
            ->where('type', 'INVITE')
            ->exists();

        if ($inviteExists) {
            return response()->json(['error' => ['message' => 'This user has already been invited to join this group.']], 409); // 409 Conflict
        }

        // Create the invitation message
        $message = new Message();
        $message->type = 'INVITE';
        $message->content = "You are invited to join the group.";
        $message->target_user_id = $targetUserId;
        $message->group_id = $groupId;
        $message->sender_id = $user->id;
        $message->save();

        // Return a response, possibly including the message details or a success message
        return response()->json([
            'message' => 'Invitation sent successfully.',
            'data' => $message,
        ], 201);
    }

    public function acceptInvitation(Request $request)
    {
        $request->validate([
            'messageId' => 'required|integer|exists:messages,id',
        ]);

        $messageId = $request->input('messageId');
        $message = Message::findOrFail($messageId);

        // Check if the message type is 'invitation'
        if ($message->type !== 'INVITE') {
            return response()->json(['message' => 'This message is not an invitation.'], 400);
        }

        // Check if the current user is the target user of the invitation
        $userId = Auth::id();
        if ($message->target_user_id !== $userId) {
            return response()->json(['message' => 'You are not the target user of this invitation.'], 403);
        }

        // Check if the user is already in a group
        $groupUserExists = GroupUser::where('user_id', $userId)->exists();
        if ($groupUserExists) {
            return response()->json(['message' => 'You are already in a group.'], 400);
        }

        // Assuming you have a way to determine the group ID from the message
        $groupId = $message->group_id;

        // Add the user to the group
        GroupUser::create([
            'group_id' => $groupId,
            'user_id' => $userId,
            'permission' => 'USER', // or 'ADMIN' depending on your logic
        ]);

        // Delete the invitation message from the database
        $message->delete();

        return response()->json(['message' => 'You have been successfully added to the group and the invitation has been deleted.', 'data' => ['groupId' => $groupId]]);
    }

    public function acceptJoinRequest(Request $request)
    {
        $request->validate([
            'messageId' => 'required|integer|exists:messages,id', // Ensure the message exists
        ]);

        $messageId = $request->input('messageId');
        $message = Message::findOrFail($messageId);

        // Check if the message type is 'JOIN'
        if ($message->type !== 'JOIN') {
            return response()->json(['message' => 'Invalid message type.'], 400);
        }

        // Retrieve the currently logged in user
        $user = Auth::user();

        // Check if the user is an admin of the group
        $isAdmin = GroupUser::where('user_id', $user->id)
            ->where('group_id', $message->group_id)
            ->where('permission', 'ADMIN')
            ->exists();

        if (!$isAdmin) {
            return response()->json(['message' => 'Unauthorized access. Not an admin of the group.'], 403);
        }

        // Check if the target user is already in a group
        $isTargetInGroup = GroupUser::where('user_id', $message->target_user_id)->exists();

        if ($isTargetInGroup) {
            return response()->json(['message' => 'Target user is already in a group.'], 400);
        }

        // Add the target user to the group
        GroupUser::create([
            'group_id' => $message->group_id,
            'user_id' => $message->target_user_id,
            'permission' => 'USER', // Default permission when joining via admin approval
        ]);

        // Optionally, delete the join request message after processing
        $message->delete();

        return response()->json(['message' => 'User added to the group successfully.']);
    }


    public function allMessages(Request $request)
    {
        // Retrieve the currently logged in user
        $user = $request->user();

        // Find all messages where the 'target_user' matches the currently logged in user's ID
        $personalMessages = Message::where('target_user_id', $user->id)->get();

        // Retrieve all group IDs where the user is an admin
        $adminGroupIds = GroupUser::where('user_id', $user->id)
            ->where('permission', 'ADMIN')
            ->pluck('group_id');

        // Fetch JOIN messages for these groups
        $joinMessages = Message::whereIn('group_id', $adminGroupIds)
            ->where('type', 'JOIN')
            ->get();

        // Merge personal messages and join messages
        $allMessages = $personalMessages->merge($joinMessages);

        // Sort messages by 'created_at' in descending order
        $sortedMessages = $allMessages->sortByDesc('created_at');

        // Convert the sorted collection to an array of objects
        $messageArray = $sortedMessages->values()->toArray();

        // Return the messages as a JSON response
        return response()->json([
            'message' => 'Messages retrieved successfully.',
            'data' => $messageArray,
        ]);
    }


    public function joinGroup(Request $request)
    {
        // Retrieve the currently authenticated user
        $user = Auth::user();

        // Check if a JOIN message already exists for this user
        $joinMessageExists = Message::where('target_user_id', $user->id)
            ->where('type', 'JOIN')
            ->exists();

        if ($joinMessageExists) {
            return response()->json(['message' => 'A join request has already been sent.'], 409); // 409 Conflict
        }

        // Check if the user is already in a group
        $groupUserExists = GroupUser::where('user_id', $user->id)->exists();
        if ($groupUserExists) {
            return response()->json(['message' => 'You cannot join another group. You already belong to a group.'], 403);
        }

        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'groupId' => 'required|exists:groups,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => ['message' => 'Validation errors', 'details' => $validator->errors()]], 400);
        }

        // Retrieve the group ID from the request
        $groupId = $request->input('groupId');

        // Create the join request message
        $message = new Message();
        $message->type = 'JOIN';
        $message->content = $user->name . " is requesting to join the group";
        $message->target_user_id = $user->id;
        $message->group_id = $groupId;
        $message->sender_id = $user->id; // Assuming the sender is the same as the target
        $message->save();

        // Return a success response
        return response()->json([
            'message' => 'Join request sent successfully.',
            'data' => $message,
        ], 201);
    }
}
