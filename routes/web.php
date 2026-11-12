<?php

use App\Enums\Role;
use App\Http\Controllers\Web\Admin\AdminPanelController;
use App\Http\Controllers\Web\Admin\DashboardController;
use App\Http\Controllers\Web\Admin\LearnerController;
use App\Http\Controllers\Web\Admin\ModuleController;
use App\Http\Controllers\Web\Admin\ResultController;
use App\Http\Controllers\Web\Admin\TestQuestionController;
use App\Http\Controllers\Web\Admin\UnitController;
use App\Http\Controllers\Web\Admin\UnitMaterialImageController;
use App\Http\Controllers\Web\Admin\UnitQuizController;
use App\Http\Controllers\Web\Auth\AdminAuthController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::get('/login', [AdminAuthController::class, 'create'])->name('login');
Route::post('/login', [AdminAuthController::class, 'store'])->name('login.store');
Route::get('/forgot-password', [AdminAuthController::class, 'forgotPassword'])->name('password.request');
Route::post('/forgot-password', [AdminAuthController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [AdminAuthController::class, 'resetPassword'])->name('password.reset');
Route::post('/reset-password', [AdminAuthController::class, 'updateResetPassword'])->name('password.update');

Route::middleware(['auth', 'role:'.Role::Admin->value])->group(function () {
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
    ->middleware(['auth', 'role:'.Role::Admin->value, 'temporary.password'])
    ->group(function () {
        Route::redirect('/', '/admin/dashboard')->name('home');
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        // FR-A06, FR-A08, FR-A09
        Route::get('/learners', [LearnerController::class, 'index'])->name('learners.index');
        Route::get('/results', [ResultController::class, 'index'])->name('results.index');
        Route::get('/results/export', [ResultController::class, 'export'])->name('results.export');
        Route::redirect('/units', '/admin/modules')->name('units.home');

        Route::get('/modules', [ModuleController::class, 'index'])->name('modules.index');
        Route::get('/modules/create', [ModuleController::class, 'create'])->name('modules.create');
        Route::post('/modules', [ModuleController::class, 'store'])->name('modules.store');
        Route::get('/modules/{module}/edit', [ModuleController::class, 'edit'])->name('modules.edit');
        Route::put('/modules/{module}', [ModuleController::class, 'update'])->name('modules.update');
        Route::delete('/modules/{module}', [ModuleController::class, 'destroy'])->name('modules.destroy');
        Route::put('/modules/{module}/status', [ModuleController::class, 'updateStatus'])->name('modules.status');
        Route::put('/modules/{module}/question-settings', [TestQuestionController::class, 'updateSettings'])
            ->name('modules.questions.settings');
        Route::get('/modules/{module}/questions/{type}', [TestQuestionController::class, 'index'])
            ->whereIn('type', ['pre', 'post'])
            ->name('modules.questions.index');
        Route::get('/modules/{module}/questions/{type}/import', [TestQuestionController::class, 'import'])
            ->whereIn('type', ['pre', 'post'])
            ->name('modules.questions.import');
        Route::get('/modules/{module}/questions/{type}/create', [TestQuestionController::class, 'create'])
            ->whereIn('type', ['pre', 'post'])
            ->name('modules.questions.create');
        Route::post('/modules/{module}/questions/{type}/bulk', [TestQuestionController::class, 'storeBulk'])
            ->whereIn('type', ['pre', 'post'])
            ->name('modules.questions.bulk');
        Route::post('/modules/{module}/questions/{type}', [TestQuestionController::class, 'store'])
            ->whereIn('type', ['pre', 'post'])
            ->name('modules.questions.store');
        Route::get('/modules/{module}/questions/{type}/{question}/edit', [TestQuestionController::class, 'edit'])
            ->whereIn('type', ['pre', 'post'])
            ->scopeBindings()
            ->name('modules.questions.edit');
        Route::put('/modules/{module}/questions/{type}/{question}', [TestQuestionController::class, 'update'])
            ->whereIn('type', ['pre', 'post'])
            ->scopeBindings()
            ->name('modules.questions.update');
        Route::delete('/modules/{module}/questions/{type}/{question}', [TestQuestionController::class, 'destroy'])
            ->whereIn('type', ['pre', 'post'])
            ->scopeBindings()
            ->name('modules.questions.destroy');

        Route::get('/modules/{module}/units', [UnitController::class, 'index'])->name('modules.units.index');
        Route::get('/modules/{module}/units/create', [UnitController::class, 'create'])->name('modules.units.create');
        Route::post('/modules/{module}/units', [UnitController::class, 'store'])->name('modules.units.store');
        Route::get('/modules/{module}/units/{unit}/edit', [UnitController::class, 'edit'])
            ->scopeBindings()
            ->name('modules.units.edit');
        Route::put('/modules/{module}/units/{unit}', [UnitController::class, 'update'])
            ->scopeBindings()
            ->name('modules.units.update');
        Route::delete('/modules/{module}/units/{unit}', [UnitController::class, 'destroy'])
            ->scopeBindings()
            ->name('modules.units.destroy');
        Route::get('/units/{unit}/quizzes', [UnitQuizController::class, 'index'])->name('units.quizzes.index');
        Route::get('/units/{unit}/quizzes/create', [UnitQuizController::class, 'create'])->name('units.quizzes.create');
        Route::post('/units/{unit}/quizzes', [UnitQuizController::class, 'store'])->name('units.quizzes.store');
        Route::get('/units/{unit}/quizzes/{quiz}/edit', [UnitQuizController::class, 'edit'])
            ->scopeBindings()
            ->name('units.quizzes.edit');
        Route::put('/units/{unit}/quizzes/{quiz}', [UnitQuizController::class, 'update'])
            ->scopeBindings()
            ->name('units.quizzes.update');
        Route::delete('/units/{unit}/quizzes/{quiz}', [UnitQuizController::class, 'destroy'])
            ->scopeBindings()
            ->name('units.quizzes.destroy');
        Route::patch('/modules/{module}/units/{unit}/move/{direction}', [UnitController::class, 'move'])
            ->whereIn('direction', ['up', 'down'])
            ->scopeBindings()
            ->name('modules.units.move');
        Route::get('/units/{unit}/preview', [UnitController::class, 'preview'])->name('units.preview');
        Route::post('/units/{unit}/material/images', [UnitMaterialImageController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('units.material-images.store');
        Route::get('/units/{unit}/material/images/{image}', [UnitMaterialImageController::class, 'show'])
            ->where('image', '[A-Za-z0-9.-]+')
            ->name('units.material-images.show');

        Route::get('/{page}', AdminPanelController::class)
            ->whereIn('page', ['questions', 'forum'])
            ->name('placeholder');
    });

// AC-09 guard only: no Superadmin management feature is part of this phase.
Route::get('/superadmin', fn () => view('auth.role-placeholder'))
    ->middleware(['auth', 'role:'.Role::Superadmin->value])
    ->name('superadmin.placeholder');
