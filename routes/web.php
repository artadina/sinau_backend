<?php

use App\Enums\Role;
use App\Http\Controllers\Web\Admin\AdminPanelController;
use App\Http\Controllers\Web\Admin\DashboardController;
use App\Http\Controllers\Web\Admin\ForumController;
use App\Http\Controllers\Web\Admin\LearnerController;
use App\Http\Controllers\Web\Admin\ModuleController;
use App\Http\Controllers\Web\Admin\ResultController;
use App\Http\Controllers\Web\Admin\TestQuestionController;
use App\Http\Controllers\Web\Admin\UnitController;
use App\Http\Controllers\Web\Admin\UnitMaterialImageController;
use App\Http\Controllers\Web\Admin\UnitQuizController;
use App\Http\Controllers\Web\Superadmin\AdminAccountController;
use App\Http\Controllers\Web\Superadmin\LearnerAccountController;
use App\Http\Controllers\Web\Superadmin\ModuleReviewController;
use App\Http\Controllers\Web\Auth\AdminAuthController;
use App\Http\Controllers\Web\NotificationController;
use App\Models\User;
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

// FR-SA03, FR-SA05: bind only active accounts of the role managed by each route.
Route::bind('user', function (string $value, \Illuminate\Routing\Route $route): User {
    $role = match (true) {
        str_starts_with($route->uri(), 'superadmin/admins/') => Role::Admin,
        str_starts_with($route->uri(), 'superadmin/learners/') => Role::Learner,
        default => abort(404),
    };

    return User::query()->where('role', $role->value)->findOrFail($value);
});

// FR-S05, AB-13: every Admin page is protected by session auth, role, and temp-password checks.
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:'.Role::Admin->value, 'temporary.password'])
    ->group(function () {
        Route::redirect('/', '/admin/dashboard')->name('home');
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])
            ->name('notifications.read');
        // FR-A06, FR-A08, FR-A09
        Route::get('/learners', [LearnerController::class, 'index'])->name('learners.index');
        Route::get('/results', [ResultController::class, 'index'])->name('results.index');
        Route::get('/results/export', [ResultController::class, 'export'])->name('results.export');
        // FR-A13, FR-A14, FR-A15: Admin-only forum and moderation routes.
        Route::get('/forum', [ForumController::class, 'index'])->name('forum.index');
        Route::get('/forum-reports', [ForumController::class, 'reports'])->name('forum-reports.index');
        Route::put('/forum-reports/{report}/resolve', [ForumController::class, 'resolve'])
            ->name('forum-reports.resolve');
        Route::get('/forum/{post}', [ForumController::class, 'show'])->name('forum.show');
        Route::post('/forum/{post}/replies', [ForumController::class, 'reply'])->name('forum.replies.store');
        Route::delete('/forum/{post}', [ForumController::class, 'destroy'])->name('forum.posts.destroy');
        Route::redirect('/units', '/admin/modules')->name('units.home');

        Route::get('/modules', [ModuleController::class, 'index'])->name('modules.index');
        Route::get('/modules/create', [ModuleController::class, 'create'])->name('modules.create');
        Route::post('/modules', [ModuleController::class, 'store'])->name('modules.store');
        Route::get('/modules/{module}', [ModuleController::class, 'show'])->middleware('module.owner')->name('modules.show');
        Route::get('/modules/{module}/edit', [ModuleController::class, 'edit'])->middleware('module.owner')->name('modules.edit');
        Route::put('/modules/{module}', [ModuleController::class, 'update'])->middleware('module.owner')->name('modules.update');
        Route::delete('/modules/{module}', [ModuleController::class, 'destroy'])
            ->middleware(['module.owner', 'module.editable'])->name('modules.destroy');
        Route::post('/modules/{module}/submit', [ModuleController::class, 'submit'])
            ->middleware(['module.owner', 'module.editable'])->name('modules.submit');
        Route::post('/modules/{module}/revision', [ModuleController::class, 'startRevision'])
            ->middleware('module.owner')->name('modules.revision');
        Route::post('/modules/{module}/extensions', [ModuleController::class, 'requestExtension'])
            ->middleware('module.owner')->name('modules.extensions.store');
        Route::put('/modules/{module}/question-settings', [TestQuestionController::class, 'updateSettings'])
            ->middleware(['module.owner', 'module.editable'])
            ->name('modules.questions.settings');
        Route::get('/modules/{module}/questions/{type}', [TestQuestionController::class, 'index'])
            ->middleware('module.owner')
            ->whereIn('type', ['pre', 'post'])
            ->name('modules.questions.index');
        Route::get('/modules/{module}/questions/{type}/import', [TestQuestionController::class, 'import'])
            ->middleware(['module.owner', 'module.editable'])
            ->whereIn('type', ['pre', 'post'])
            ->name('modules.questions.import');
        Route::get('/modules/{module}/questions/{type}/create', [TestQuestionController::class, 'create'])
            ->middleware(['module.owner', 'module.editable'])
            ->whereIn('type', ['pre', 'post'])
            ->name('modules.questions.create');
        Route::post('/modules/{module}/questions/{type}/bulk', [TestQuestionController::class, 'storeBulk'])
            ->middleware(['module.owner', 'module.editable'])
            ->whereIn('type', ['pre', 'post'])
            ->name('modules.questions.bulk');
        Route::post('/modules/{module}/questions/{type}', [TestQuestionController::class, 'store'])
            ->middleware(['module.owner', 'module.editable'])
            ->whereIn('type', ['pre', 'post'])
            ->name('modules.questions.store');
        Route::get('/modules/{module}/questions/{type}/{question}/edit', [TestQuestionController::class, 'edit'])
            ->middleware(['module.owner', 'module.editable'])
            ->whereIn('type', ['pre', 'post'])
            ->scopeBindings()
            ->name('modules.questions.edit');
        Route::put('/modules/{module}/questions/{type}/{question}', [TestQuestionController::class, 'update'])
            ->middleware(['module.owner', 'module.editable'])
            ->whereIn('type', ['pre', 'post'])
            ->scopeBindings()
            ->name('modules.questions.update');
        Route::delete('/modules/{module}/questions/{type}/{question}', [TestQuestionController::class, 'destroy'])
            ->middleware(['module.owner', 'module.editable'])
            ->whereIn('type', ['pre', 'post'])
            ->scopeBindings()
            ->name('modules.questions.destroy');

        Route::get('/modules/{module}/units', [UnitController::class, 'index'])->middleware('module.owner')->name('modules.units.index');
        Route::get('/modules/{module}/units/create', [UnitController::class, 'create'])
            ->middleware(['module.owner', 'module.editable'])->name('modules.units.create');
        Route::post('/modules/{module}/units', [UnitController::class, 'store'])
            ->middleware(['module.owner', 'module.editable'])->name('modules.units.store');
        Route::get('/modules/{module}/units/{unit}/edit', [UnitController::class, 'edit'])
            ->middleware(['module.owner', 'module.editable'])
            ->scopeBindings()
            ->name('modules.units.edit');
        Route::put('/modules/{module}/units/{unit}', [UnitController::class, 'update'])
            ->middleware(['module.owner', 'module.editable'])
            ->scopeBindings()
            ->name('modules.units.update');
        Route::delete('/modules/{module}/units/{unit}', [UnitController::class, 'destroy'])
            ->middleware(['module.owner', 'module.editable'])
            ->scopeBindings()
            ->name('modules.units.destroy');
        Route::get('/units/{unit}/quizzes', [UnitQuizController::class, 'index'])->middleware('module.owner')->name('units.quizzes.index');
        Route::get('/units/{unit}/quizzes/create', [UnitQuizController::class, 'create'])
            ->middleware(['module.owner', 'module.editable'])->name('units.quizzes.create');
        Route::post('/units/{unit}/quizzes', [UnitQuizController::class, 'store'])
            ->middleware(['module.owner', 'module.editable'])->name('units.quizzes.store');
        Route::get('/units/{unit}/quizzes/{quiz}/edit', [UnitQuizController::class, 'edit'])
            ->middleware(['module.owner', 'module.editable'])
            ->scopeBindings()
            ->name('units.quizzes.edit');
        Route::put('/units/{unit}/quizzes/{quiz}', [UnitQuizController::class, 'update'])
            ->middleware(['module.owner', 'module.editable'])
            ->scopeBindings()
            ->name('units.quizzes.update');
        Route::delete('/units/{unit}/quizzes/{quiz}', [UnitQuizController::class, 'destroy'])
            ->middleware(['module.owner', 'module.editable'])
            ->scopeBindings()
            ->name('units.quizzes.destroy');
        Route::patch('/modules/{module}/units/{unit}/move/{direction}', [UnitController::class, 'move'])
            ->middleware(['module.owner', 'module.editable'])
            ->whereIn('direction', ['up', 'down'])
            ->scopeBindings()
            ->name('modules.units.move');
        Route::get('/units/{unit}/preview', [UnitController::class, 'preview'])
            ->middleware('module.owner')->name('units.preview');
        Route::post('/units/{unit}/material/images', [UnitMaterialImageController::class, 'store'])
            ->middleware(['module.owner', 'module.editable', 'throttle:30,1'])
            ->name('units.material-images.store');
        Route::get('/units/{unit}/material/images/{image}', [UnitMaterialImageController::class, 'show'])
            ->middleware('module.owner')
            ->where('image', '[A-Za-z0-9.-]+')
            ->name('units.material-images.show');

        Route::get('/{page}', AdminPanelController::class)
            ->whereIn('page', ['questions', 'forum'])
            ->name('placeholder');
    });

// FR-SA02–FR-SA07, FR-S05: content review is read-only and separate from scores and forum.
Route::prefix('superadmin')
    ->name('superadmin.')
    ->middleware(['auth', 'role:'.Role::Superadmin->value])
    ->group(function (): void {
        Route::redirect('/', '/superadmin/admins')->name('home');
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])
            ->name('notifications.read');
        Route::get('/modules', [ModuleReviewController::class, 'index'])->name('modules.index');
        Route::get('/modules/{module}', [ModuleReviewController::class, 'show'])->name('modules.show');
        Route::get('/modules/{module}/units/{unit}/material/images/{image}', [
            UnitMaterialImageController::class,
            'showForSuperadmin',
        ])->where('image', '[A-Za-z0-9.-]+')
            ->scopeBindings()
            ->name('modules.units.material-images.show');
        Route::post('/modules/{module}/approve', [ModuleReviewController::class, 'approve'])->name('modules.approve');
        Route::post('/modules/{module}/revision', [ModuleReviewController::class, 'requestRevision'])
            ->name('modules.revision');
        Route::post('/modules/{module}/takedown', [ModuleReviewController::class, 'takedown'])
            ->name('modules.takedown');
        Route::post('/extensions/{extension}/decision', [ModuleReviewController::class, 'decideExtension'])
            ->name('extensions.decision');

        Route::get('/admins', [AdminAccountController::class, 'index'])->name('admins.index');
        Route::get('/admins/create', [AdminAccountController::class, 'create'])->name('admins.create');
        Route::post('/admins', [AdminAccountController::class, 'store'])->name('admins.store');
        Route::get('/admins/{user}', [AdminAccountController::class, 'show'])->name('admins.show');
        Route::get('/admins/{user}/edit', [AdminAccountController::class, 'edit'])->name('admins.edit');
        Route::put('/admins/{user}', [AdminAccountController::class, 'update'])->name('admins.update');
        Route::put('/admins/{user}/role', [AdminAccountController::class, 'updateRole'])
            ->name('admins.role');
        Route::delete('/admins/{user}', [AdminAccountController::class, 'destroy'])->name('admins.destroy');

        Route::get('/learners', [LearnerAccountController::class, 'index'])->name('learners.index');
        Route::get('/learners/{user}', [LearnerAccountController::class, 'show'])->name('learners.show');
        Route::get('/learners/{user}/edit', [LearnerAccountController::class, 'edit'])->name('learners.edit');
        Route::put('/learners/{user}', [LearnerAccountController::class, 'update'])->name('learners.update');
        Route::put('/learners/{user}/role', [LearnerAccountController::class, 'updateRole'])
            ->name('learners.role');
        Route::delete('/learners/{user}', [LearnerAccountController::class, 'destroy'])->name('learners.destroy');
    });
