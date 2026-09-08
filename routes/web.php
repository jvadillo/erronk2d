<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChallengeController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SetupController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/forgot-password', fn () => Inertia::render('Login', ['mode' => 'forgot']));
    Route::post('/forgot-password', [AuthController::class, 'forgot'])->middleware('throttle:3,1');
    Route::get('/reset-password/{token}', fn (string $token) => Inertia::render('Login', ['mode' => 'reset', 'token' => $token, 'email' => request('email')]))->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->middleware('throttle:5,1');
});
Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/', [ChallengeController::class, 'index'])->name('dashboard');
    Route::post('/challenges', [ChallengeController::class, 'store']);
    Route::get('/challenges/{challenge}', [ChallengeController::class, 'show']);
    Route::post('/challenges/{challenge}', [ChallengeController::class, 'update']);
    Route::get('/challenges/{challenge}/history', [ChallengeController::class, 'history']);
    Route::get('/setup', [SetupController::class, 'index']);
    Route::post('/setup/{entity}', [SetupController::class, 'store']);
    Route::post('/imports', [ImportController::class, 'import'])->middleware('throttle:20,1');
    Route::get('/reports', [ReportController::class, 'index']);
});
