<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\TestAttempt;
use App\Models\User;
use App\Services\AdminResultsService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    // FR-A10, AC-40
    public function __invoke(AdminResultsService $results): View
    {
        return view('admin.dashboard.index', [
            'title' => 'Dashboard',
            'learnerCount' => User::query()->where('role', Role::Learner->value)->count(),
            'moduleCount' => Module::query()->count(),
            'completedTestCount' => TestAttempt::query()->whereNotNull('completed_at')->count(),
            'moduleAverages' => $results->moduleAverages(),
        ]);
    }
}
