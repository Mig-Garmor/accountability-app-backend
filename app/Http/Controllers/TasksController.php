<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TasksController extends Controller
{
    public function store(Request $request)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'challengeId' => 'required|exists:challenges,id', // Ensure the challenge exists
        ]);

        // Retrieve the authenticated user's ID
        $userId = Auth::id();

        // Optional: Check if the user is part of the challenge before proceeding
        // This might involve checking a pivot table or a specific method in your User or Challenge model
        // Example check (adjust according to your application logic):
        // $challenge = Challenge::findOrFail($validated['challenge_id']);
        // if (!$challenge->users->contains($userId)) {
        //     return response()->json(['message' => 'User is not part of this challenge'], 403);
        // }

        // Create a new task and associate it with the user and challenge
        $task = new Task();
        $task->name = $validated['name'];
        $task->user_id = $userId; // Associating the task with the authenticated user
        $task->challenge_id = $validated['challengeId']; // Associating the task with the provided challenge
        $task->save();

        // Return a response or redirect the user
        return response()->json(['message' => 'Task created successfully', 'task' => $task], 201);
    }
}
