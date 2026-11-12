<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LearnerController extends Controller
{
    // FR-A06, AC-38: read-only list, excluding soft-deleted/anonymous accounts.
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ], [
            'date_from.date' => 'Tanggal mulai tidak valid.',
            'date_to.date' => 'Tanggal selesai tidak valid.',
            'date_to.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
        ]);

        $learners = User::query()
            ->where('role', Role::Learner->value)
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($matches) use ($search): void {
                    $matches->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['date_from'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.learners.index', [
            'title' => 'Daftar Learner',
            'learners' => $learners,
            'filters' => $filters,
        ]);
    }
}
