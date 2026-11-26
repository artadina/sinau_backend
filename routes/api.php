<?php

use App\Enums\Role;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\LearnerMaterialController;
use App\Http\Controllers\Web\Admin\UnitMaterialImageController;
use Illuminate\Support\Facades\Route;

// FR-L01–FR-L06, FR-S01: learner account API only; role comes from the backend.
Route::post('/register', [AccountController::class, 'register'])
    ->middleware('throttle:api-register')
    ->name('api.register');
Route::post('/login', [AccountController::class, 'login'])
    ->middleware('throttle:api-login')
    ->name('api.login');
Route::post('/forgot-password', [AccountController::class, 'forgotPassword'])
    ->middleware('throttle:api-forgot-password')
    ->name('api.password.email');

Route::middleware(['auth:sanctum', 'role:'.Role::Learner->value])->group(function (): void {
    Route::post('/logout', [AccountController::class, 'logout'])->name('api.logout');
    Route::get('/me', [AccountController::class, 'me'])->name('api.me');
    Route::get('/user', [AccountController::class, 'me'])->name('api.user');
    Route::post('/consent', [AccountController::class, 'consent'])->name('api.consent');
    Route::put('/profile', [AccountController::class, 'updateProfile'])->name('api.profile.update');
    Route::put('/profile/password', [AccountController::class, 'updatePassword'])
        ->name('api.profile.password');
    Route::delete('/account', [AccountController::class, 'deleteAccount'])->name('api.account.destroy');
});

// FR-L08–FR-L14, FR-S03: learner material API is scoped to the token owner and requires consent.
Route::middleware(['auth:sanctum', 'role:'.Role::Learner->value, 'learner.consent'])->group(function (): void {
    Route::get('/modules', [LearnerMaterialController::class, 'index'])->name('api.modules.index');
    Route::get('/modules/{module}', [LearnerMaterialController::class, 'showModule'])->name('api.modules.show');
    Route::get('/units/{unit}', [LearnerMaterialController::class, 'showUnit'])->name('api.units.show');
    Route::put('/units/{unit}/progress', [LearnerMaterialController::class, 'updateProgress'])
        ->name('api.units.progress.update');
    Route::post('/units/{unit}/complete', [LearnerMaterialController::class, 'complete'])
        ->name('api.units.complete');
    Route::get('/home', [LearnerMaterialController::class, 'home'])->name('api.home');
});

Route::get('/units/{unit}/material/images/{image}', [UnitMaterialImageController::class, 'showForLearner'])
    ->middleware(['auth:sanctum', 'role:learner', 'learner.consent'])
    ->where('image', '[A-Za-z0-9.-]+')
    ->name('api.units.material-images.show');
