<?php

namespace App\Providers;

use App\Models\ForumReport;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // FR-A15: keep the sidebar badge accurate on every Admin page.
        View::composer('layouts.admin', function (ViewInstance $view): void {
            $view->with(
                'pendingForumReports',
                ForumReport::query()->where('status', 'pending')->count(),
            );
        });

        // FR-L01, FR-L03, FR-L04: apply localized built-in limits to public account routes.
        foreach ([
            'api-login' => [5, 'Terlalu banyak percobaan login. Silakan coba lagi beberapa saat.'],
            'api-register' => [5, 'Terlalu banyak percobaan. Silakan coba lagi beberapa saat.'],
            'api-forgot-password' => [3, 'Terlalu banyak permintaan reset password. Silakan coba lagi beberapa saat.'],
        ] as $name => [$perMinute, $message]) {
            RateLimiter::for($name, static function (Request $request) use ($perMinute, $message): Limit {
                return Limit::perMinute($perMinute)
                    ->by($request->ip())
                    ->response(static fn (Request $request, array $headers): JsonResponse => response()->json(
                        ['message' => $message],
                        429,
                        $headers,
                    ));
            });
        }
    }
}
