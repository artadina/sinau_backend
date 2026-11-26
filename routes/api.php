<?php

use App\Enums\Role;
use App\Http\Controllers\Api\AccountController;
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

Route::get('/units/{unit}/material/images/{image}', [UnitMaterialImageController::class, 'showForLearner'])
    ->middleware(['auth:sanctum', 'role:learner'])
    ->where('image', '[A-Za-z0-9.-]+')
    ->name('api.units.material-images.show');
