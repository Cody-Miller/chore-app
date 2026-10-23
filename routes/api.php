<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ChoreApiController;
use App\Http\Controllers\Api\ChoreLogApiController;
use App\Http\Controllers\Api\PetApiController;
use App\Http\Controllers\Api\PillApiController;
use App\Http\Controllers\Api\PillLogApiController;
use App\Http\Controllers\Api\StatsApiController;
use App\Http\Controllers\Api\SummaryApiController;
use App\Http\Controllers\Api\UserApiController;
use App\Http\Middleware\UseViewerTimezone;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
| Pets, pills and chores are identified by slug or name. Fixed paths like
| /chores/due are declared before /chores/{chore} so they aren't read as names.
| Times are shown and read in the token's timezone (see UseViewerTimezone).
| See docs/API.md for the full reference.
|
*/

Route::middleware(['auth:sanctum', UseViewerTimezone::class])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/users', [UserApiController::class, 'index']);
    Route::get('/summary', [SummaryApiController::class, 'index']);
    Route::get('/stats', [StatsApiController::class, 'index']);

    // Chores
    Route::get('/chores', [ChoreApiController::class, 'index']);
    Route::get('/chores/due', [ChoreApiController::class, 'due']);
    Route::get('/chores/upcoming', [ChoreApiController::class, 'upcoming']);
    Route::get('/chores/one-time', [ChoreApiController::class, 'oneTime']);
    Route::get('/chores/snoozed', [ChoreApiController::class, 'snoozed']);
    Route::post('/chores/complete', [ChoreApiController::class, 'complete']);
    Route::get('/chores/{chore}', [ChoreApiController::class, 'show']);
    Route::post('/chores/{chore}/complete', [ChoreApiController::class, 'complete']);
    Route::post('/chores/{chore}/snooze', [ChoreApiController::class, 'snooze']);
    Route::delete('/chores/{chore}/snooze', [ChoreApiController::class, 'unsnooze']);

    // Chore history and undo
    Route::get('/chore-logs', [ChoreLogApiController::class, 'index']);
    Route::delete('/chore-logs/{log}', [ChoreLogApiController::class, 'destroy']);

    // Pets
    Route::get('/pets', [PetApiController::class, 'index']);
    Route::get('/pets/{pet}', [PetApiController::class, 'show']);
    Route::get('/pets/{pet}/pills', [PetApiController::class, 'pills']);

    // Pills
    Route::get('/pills', [PillApiController::class, 'index']);
    Route::get('/pills/due', [PillApiController::class, 'due']);
    Route::get('/pills/upcoming', [PillApiController::class, 'upcoming']);
    Route::get('/pills/today', [PillApiController::class, 'today']);
    Route::post('/pills/give', [PillApiController::class, 'give']);
    Route::get('/pills/{pill}', [PillApiController::class, 'show']);
    Route::post('/pills/{pill}/give', [PillApiController::class, 'give']);
    Route::post('/record-medication', [PillApiController::class, 'give']); // Older name for /pills/give

    // Pill history and undo
    Route::get('/pill-logs', [PillLogApiController::class, 'index']);
    Route::delete('/pill-logs/{log}', [PillLogApiController::class, 'destroy']);
});
