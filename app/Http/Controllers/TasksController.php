<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use App\Events\TaskCompleted;
use App\Models\CompletedTask;
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

    public function completeTask(Request $request)
    {
        $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'day' => 'required|integer|between:1,28',
        ]);

        // Check for existing entry
        $existingEntry = CompletedTask::where('task_id', $request->task_id)
            ->where('day', $request->day)
            ->first();

        if ($existingEntry) {
            // Delete the existing entry
            $existingEntry->delete();

            // Dispatch the event
            event(new TaskCompleted(['completedTask' => $existingEntry, 'action' => 'REMOVE', 'userId' => $request->user()->id]));

            return response()->json([
                'message' => 'Completed task deleted successfully.',
                'data' => $existingEntry,
            ]);
        } else {
            // Add the new completed task entry
            $completedTask = CompletedTask::create([
                'task_id' => $request->task_id,
                'day' => $request->day,
            ]);

            // Dispatch the event
            event(new TaskCompleted(['completedTask' => $completedTask, 'action' => 'ADD', 'userId' => $request->user()->id]));

            return response()->json([
                'message' => 'Completed task added successfully.',
                'data' => $completedTask,
            ]);
        }
    }
    /**
     * Delete a task.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function deleteTask($id)
    {
        // Find the task along with its completed tasks to avoid N+1 query issue
        $task = Task::with('completedTasks')->findOrFail($id);

        // Check if the task has any associated completed tasks
        if ($task->completedTasks()->count() > 0) {
            // If there are associated completed tasks, return an error response
            return response()->json([
                'message' => 'Task cannot be deleted because it has associated completed tasks.'
            ], 403); // 403 Forbidden
        }

        // If there are no associated completed tasks, delete the task
        $task->delete();

        return response()->json(['messages' => 'Task deleted successfully'], 200);
    }
}
