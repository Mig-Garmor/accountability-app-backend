<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message; // Ensure you have the Message model created
use App\Models\User; // Assuming you have a User model
use App\Models\Group; // Assuming you have a Group model
use Illuminate\Support\Facades\Validator;


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

    public function allMessages(Request $request)
    {
        // Retrieve the currently logged in user
        $user = $request->user();

        // Find all messages where the 'target_user' matches the currently logged in user's ID
        $messages = Message::where('target_user_id', $user->id)->get();

        // Return the messages as a JSON response
        return response()->json([
            'message' => 'Messages retrieved successfully.',
            'data' => $messages,
        ]);
    }
}