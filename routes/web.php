<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChoreController;
use App\Http\Controllers\ChoreLog;
use App\Http\Controllers\GraphsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect('/login');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Graphs
Route::get('/graphs', [GraphsController::class, 'index'])->name('graphs.index');

// About
Route::get('/about', function () {
    return view('about');
})->name('about');

// Actions
Route::resource('chorelog', ChoreLog::class)->middleware(['auth', 'verified']);

// Chores
Route::resource('chores', ChoreController::class)->middleware(['auth', 'verified']);
