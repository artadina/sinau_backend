<?php

namespace App\Providers;

use App\Models\ForumReport;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
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
    }
}
