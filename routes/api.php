<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\ChallengeController;

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
//Challenge
Route::middleware('auth:sanctum')->post('/challenge', [ChallengeController::class, 'createChallenge']);
