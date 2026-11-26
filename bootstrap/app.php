<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureLearnerConsent;
use App\Http\Middleware\EnsureEditableModule;
use App\Http\Middleware\EnsureModuleOwner;
use App\Http\Middleware\RequireAdminPasswordChange;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
            'learner.consent' => EnsureLearnerConsent::class,
            'module.owner' => EnsureModuleOwner::class,
            'module.editable' => EnsureEditableModule::class,
            'temporary.password' => RequireAdminPasswordChange::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Data yang diminta tidak ditemukan atau belum dapat diakses.',
                ], 404);
            }
        });
    })->create();
