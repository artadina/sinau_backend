<?php

use App\Http\Controllers\Web\Admin\AdminPanelController;
use App\Http\Controllers\Web\Auth\AdminAuthController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::get('/login', [AdminAuthController::class, 'create'])->name('login');
Route::post('/login', [AdminAuthController::class, 'store'])->name('login.store');
Route::get('/forgot-password', [AdminAuthController::class, 'forgotPassword'])->name('password.request');
Route::post('/forgot-password', [AdminAuthController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [AdminAuthController::class, 'resetPassword'])->name('password.reset');
Route::post('/reset-password', [AdminAuthController::class, 'updateResetPassword'])->name('password.update');

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/password/temporary', [AdminAuthController::class, 'editTemporaryPassword'])
        ->name('password.temporary.edit');
    Route::put('/password/temporary', [AdminAuthController::class, 'updateTemporaryPassword'])
        ->name('password.temporary.update');
});

Route::post('/logout', [AdminAuthController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// FR-S05, AB-13: every Admin page is protected by session auth, role, and temp-password checks.
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:admin', 'temporary.password'])
    ->group(function () {
        Route::redirect('/', '/admin/dashboard')->name('home');
        Route::get('/dashboard', AdminPanelController::class)
            ->defaults('page', 'dashboard')
            ->name('dashboard');
        Route::get('/{page}', AdminPanelController::class)
            ->whereIn('page', ['modules', 'units', 'questions', 'forum', 'learners', 'results'])
            ->name('placeholder');
    });

// AC-09 guard only: no Superadmin management feature is part of this phase.
Route::get('/superadmin', fn () => view('auth.role-placeholder'))
    ->middleware(['auth', 'role:superadmin'])
    ->name('superadmin.placeholder');
