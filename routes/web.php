<?php

use App\Http\Controllers\AcademicContextController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChallengeController;
use App\Http\Controllers\ChallengeRubricController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SetupController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->middleware('throttle:10,1')->name('google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->middleware('throttle:20,1')->name('google.callback');
    Route::post('/auth/google/link', [GoogleAuthController::class, 'link'])->middleware('throttle:10,1')->name('google.link');
    Route::get('/auth/google/cancel', [GoogleAuthController::class, 'cancel'])->name('google.cancel');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/forgot-password', fn () => Inertia::render('Login', ['mode' => 'forgot']));
    Route::post('/forgot-password', [AuthController::class, 'forgot'])->middleware('throttle:3,1');
    Route::get('/reset-password/{token}', fn (string $token) => Inertia::render('Login', ['mode' => 'reset', 'token' => $token, 'email' => request('email')]))->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->middleware('throttle:5,1');
});
Route::middleware(['auth', 'active', 'academic'])->group(function () {
    Route::post('/academic-context', [AcademicContextController::class, 'update'])->name('academic-context.update');
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/', [ChallengeController::class, 'index'])->name('dashboard');
    Route::post('/challenges', [ChallengeController::class, 'store']);
    Route::get('/challenges/{challenge}', [ChallengeController::class, 'show'])->name('challenges.show');
    Route::post('/challenges/{challenge}', [ChallengeController::class, 'update'])->name('challenges.update');
    Route::get('/challenges/{challenge}/rubrics/{kind}/edit', [ChallengeRubricController::class, 'edit'])->whereIn('kind', ['team', 'transversal'])->name('challenges.rubrics.edit');
    Route::post('/challenges/{challenge}/rubrics/{kind}/preview', [ChallengeRubricController::class, 'preview'])->whereIn('kind', ['team', 'transversal'])->name('challenges.rubrics.preview');
    Route::get('/challenges/{challenge}/history', [ChallengeController::class, 'history']);
    Route::get('/setup/rubrics/create', [SetupController::class, 'rubricEditor'])->name('rubrics.create');
    Route::get('/setup/rubrics/{rubric}/edit', [SetupController::class, 'rubricEditor'])->whereNumber('rubric')->name('rubrics.edit');
    Route::get('/setup', [SetupController::class, 'index'])->name('setup.index');
    Route::get('/setup/{section}', [SetupController::class, 'index'])->name('setup.section');
    Route::post('/registrations/{registration}', [RegistrationController::class, 'update'])->name('registrations.update');
    Route::post('/setup/{entity}', [SetupController::class, 'store'])->name('setup.store');
    Route::post('/students/lookup', [SetupController::class, 'lookupStudent'])->middleware('throttle:20,1')->name('students.lookup');
    Route::post('/imports', [ImportController::class, 'import'])->middleware('throttle:20,1');
    Route::get('/reports', [ReportController::class, 'index']);
});
