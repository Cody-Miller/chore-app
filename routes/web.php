<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChoreController;
use App\Http\Controllers\ChoreLogController;
use App\Http\Controllers\ChoreSnoozeController;
use App\Http\Controllers\GraphsController;
use App\Http\Controllers\PetController;
use App\Http\Controllers\PillController;
use App\Http\Controllers\PillLogController;
use App\Http\Controllers\PillDashboardController;
use App\Http\Controllers\ApiTokenController;

require __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider, and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

//@todo should the tag line be: "Don't be bored... Bee chored"
Route::get('/', function () {
    return redirect('/login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // API Token Management
    Route::post('/profile/api-tokens', [ApiTokenController::class, 'store'])->name('api-tokens.store');
    Route::delete('/profile/api-tokens/{token}', [ApiTokenController::class, 'destroy'])->name('api-tokens.destroy');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Graphs
    Route::get('/graphs', [GraphsController::class, 'index'])->name('graphs.index');

    // About
    Route::get('/about', function () {
        return view('about');
    })->name('about');

    // ChoreLog
    Route::get('chorelog', [ChoreLogController::class, 'index'])->name('chorelog.index');
    Route::post('/chorelog/{chore}', [ChoreLogController::class, 'store'])->name('chorelog.store');
    Route::get('chorelog/{chorelog}/edit', [ChoreLogController::class, 'edit'])->name('chorelog.edit');
    Route::patch('chorelog/{chorelog}', [ChoreLogController::class, 'update'])->name('chorelog.update');
    Route::delete('chorelog/{chorelog}', [ChoreLogController::class, 'destroy'])->name('chorelog.destroy');

    // Chore Snooze
    Route::post('/chores/{chore}/snooze', [ChoreSnoozeController::class, 'store'])->name('chores.snooze');
    Route::delete('/chores/{chore}/snooze', [ChoreSnoozeController::class, 'destroy'])->name('chores.unsnooze');

    // Chores
    Route::resource('chores', ChoreController::class);

    // Pill Dashboard
    Route::get('/pills/dashboard', [PillDashboardController::class, 'index'])->name('pills.dashboard');

    // Pets
    Route::resource('pets', PetController::class);

    // Pills
    Route::resource('pills', PillController::class);

    // Pill Logs
    Route::get('pilllogs', [PillLogController::class, 'index'])->name('pilllogs.index');
    Route::post('/pilllogs/{pill}', [PillLogController::class, 'store'])->name('pilllogs.store');
    Route::get('pilllogs/{pilllog}/edit', [PillLogController::class, 'edit'])->name('pilllogs.edit');
    Route::patch('pilllogs/{pilllog}', [PillLogController::class, 'update'])->name('pilllogs.update');
    Route::delete('pilllogs/{pilllog}', [PillLogController::class, 'destroy'])->name('pilllogs.destroy');
});
