<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\TasksController;
use App\Http\Controllers\MessagesController;
use App\Http\Controllers\ChallengeController;
use App\Http\Controllers\WebsocketButtonMessage;
use App\Http\Controllers\CompletedTaskController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

//Authentication
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [UserController::class, 'register']);
Route::get('/register', [UserController::class, 'register']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
//Group
Route::middleware('auth:sanctum')->post('/group', [GroupController::class, 'storeGroup']);
Route::middleware('auth:sanctum')->get('/group/{groupId}', [GroupController::class, 'getGroup']);
Route::middleware('auth:sanctum')->get('/group/{groupId}/activeChallenge', [GroupController::class, 'getActiveChallenge']);
Route::middleware('auth:sanctum')->delete('/group/{groupId}/user/{userId}', [GroupController::class, 'removeUserFromGroup']);
Route::middleware('auth:sanctum')->get('/groups', [GroupController::class, 'getAllGroups']);


//Messages
Route::middleware('auth:sanctum')->get('/messages', [MessagesController::class, 'allMessages']);
Route::middleware('auth:sanctum')->post('/messages/invite', [MessagesController::class, 'inviteUser']);
Route::middleware('auth:sanctum')->post('/messages/invite/accept', [MessagesController::class, 'acceptInvitation']);
Route::middleware('auth:sanctum')->post('/messages/join', [MessagesController::class, 'joinGroup']);
Route::middleware('auth:sanctum')->post('/messages/join/accept', [MessagesController::class, 'acceptJoinRequest']);



//Challenge
Route::middleware('auth:sanctum')->post('/challenge', [ChallengeController::class, 'createChallenge']);
Route::middleware('auth:sanctum')->post('/challenge/enter', [ChallengeController::class, 'enterChallenge']);
Route::middleware('auth:sanctum')->delete('/challenge/{challengeId}', [ChallengeController::class, 'deleteChallenge']);
Route::middleware('auth:sanctum')->post('/challenge/{challengeId}/exit', [ChallengeController::class, 'exitChallenge']);



//Users
Route::middleware('auth:sanctum')->get('/users', [UserController::class, 'allUsers']);
Route::middleware('auth:sanctum')->get('/users/current', [UserController::class, 'currentUser']);

//Tasks
Route::middleware('auth:sanctum')->post('/tasks', [TasksController::class, 'store']);
Route::middleware('auth:sanctum')->delete('/tasks/{taskId}', [TasksController::class, 'deleteTask']);

//Websocket broadcast
Route::middleware('auth:sanctum')->post('/completedTask', [TasksController::class, 'completeTask']);
//--Websocket broadcast
