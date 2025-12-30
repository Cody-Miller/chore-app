<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PetApiController;
use App\Http\Controllers\Api\PillApiController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Pet Medication API Routes
Route::middleware('auth:sanctum')->group(function () {
    // List all pets
    Route::get('/pets', [PetApiController::class, 'index']);

    // List pills for a specific pet
    Route::get('/pets/{petName}/pills', [PillApiController::class, 'index']);

    // Record medication dose (smart logic - accepts pet and optional pill in body)
    Route::post('/record-medication', [PillApiController::class, 'recordMedication']);
});
