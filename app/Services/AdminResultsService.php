<?php

namespace App\Services;

use App\Enums\Role;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminResultsService
{
    private const PAGE_SIZE = 15;

    // FR-A08, FR-A09, AC-39: the table and CSV share the same filtered query and formatting.
    public function rows(array $filters, bool $paginate = true): Collection|\Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->resultsQuery($filters);

        if ($paginate) {
            return $query->paginate(self::PAGE_SIZE)
                ->withQueryString()
                ->through(fn (object $row): array => $this->formatRow($row));
        }

        return $query->get()->map(fn (object $row): array => $this->formatRow($row));
    }

    // FR-A10, AC-40: averages use the same latest completed attempts as the recap.
    public function moduleAverages(): Collection
    {
        $pre = $this->latestAttemptsQuery('pre');
        $post = $this->latestAttemptsQuery('post');

        $pairedScores = DB::query()
            ->fromSub($pre, 'latest_pre')
            ->joinSub($post, 'latest_post', function ($join): void {
                $join->on('latest_post.module_id', '=', 'latest_pre.module_id')
                    ->on('latest_post.user_id', '=', 'latest_pre.user_id');
            })
            ->join('users', 'users.id', '=', 'latest_pre.user_id')
            ->where('users.role', Role::Learner->value)
            ->whereNull('users.deleted_at')
            ->select('latest_pre.module_id', 'latest_pre.score as pre_score', 'latest_post.score as post_score');

        $averages = DB::query()
            ->fromSub($pairedScores, 'paired_scores')
            ->select(
                'paired_scores.module_id',
                DB::raw('AVG(paired_scores.pre_score) as average_pre'),
                DB::raw('AVG(paired_scores.post_score) as average_post'),
            )
            ->groupBy('paired_scores.module_id');

        return DB::table('modules')
            ->leftJoinSub($averages, 'module_averages', function ($join): void {
                $join->on('module_averages.module_id', '=', 'modules.id');
            })
            ->select('modules.id', 'modules.title', 'module_averages.average_pre', 'module_averages.average_post')
            ->orderBy('modules.title')
            ->get();
    }

    // FR-A08, AC-39: aggregate attempts and unit progress without per-row queries.
    private function resultsQuery(array $filters): Builder
    {
        $eligiblePairs = DB::table('test_attempts as eligible')
            ->whereNotNull('eligible.completed_at');

        $this->applyAttemptFilters($eligiblePairs, $filters, 'eligible');

        $eligiblePairs
            ->select('eligible.user_id', 'eligible.module_id')
            ->groupBy('eligible.user_id', 'eligible.module_id');

        $latestPre = $this->latestAttemptsQuery('pre', $filters);
        $latestPost = $this->latestAttemptsQuery('post', $filters);

        $activeUnitCounts = DB::table('units')
            ->where('is_active', true)
            ->select('module_id', DB::raw('COUNT(*) as active_units'))
            ->groupBy('module_id');

        $completedUnitCounts = DB::table('learner_unit_progress as progress')
            ->join('units', 'units.id', '=', 'progress.unit_id')
            ->where('units.is_active', true)
            ->where('progress.is_completed', true)
            ->select(
                'progress.user_id',
                'units.module_id',
                DB::raw('COUNT(*) as completed_units'),
            )
            ->groupBy('progress.user_id', 'units.module_id');

        $query = DB::query()
            ->fromSub($eligiblePairs, 'eligible_pairs')
            ->join('users', 'users.id', '=', 'eligible_pairs.user_id')
            ->join('modules', 'modules.id', '=', 'eligible_pairs.module_id')
            ->leftJoinSub($latestPre, 'latest_pre', function ($join): void {
                $join->on('latest_pre.user_id', '=', 'eligible_pairs.user_id')
                    ->on('latest_pre.module_id', '=', 'eligible_pairs.module_id');
            })
            ->leftJoinSub($latestPost, 'latest_post', function ($join): void {
                $join->on('latest_post.user_id', '=', 'eligible_pairs.user_id')
                    ->on('latest_post.module_id', '=', 'eligible_pairs.module_id');
            })
            ->leftJoinSub($activeUnitCounts, 'active_unit_counts', function ($join): void {
                $join->on('active_unit_counts.module_id', '=', 'eligible_pairs.module_id');
            })
            ->leftJoinSub($completedUnitCounts, 'completed_unit_counts', function ($join): void {
                $join->on('completed_unit_counts.user_id', '=', 'eligible_pairs.user_id')
                    ->on('completed_unit_counts.module_id', '=', 'eligible_pairs.module_id');
            })
            ->where('users.role', Role::Learner->value)
            ->whereNull('users.deleted_at')
            ->select(
                'users.id as user_id',
                'users.name as learner_name',
                'users.email as learner_email',
                'modules.id as module_id',
                'modules.title as module_title',
                'latest_pre.score as pre_score',
                'latest_post.score as post_score',
                DB::raw('COALESCE(completed_unit_counts.completed_units, 0) as completed_units'),
                DB::raw('COALESCE(active_unit_counts.active_units, 0) as active_units'),
            )
            ->orderBy('users.name')
            ->orderBy('modules.title')
            ->orderBy('users.id')
            ->orderBy('modules.id');

        if (! empty($filters['module_id'])) {
            $query->where('eligible_pairs.module_id', $filters['module_id']);
        }

        return $query;
    }

    // FR-A08, FR-A10: resolve the latest completed attempt deterministically.
    private function latestAttemptsQuery(string $type, array $filters = []): Builder
    {
        $attempts = DB::table('test_attempts as attempts')
            ->where('attempts.type', $type)
            ->whereNotNull('attempts.completed_at');

        $this->applyAttemptFilters($attempts, $filters, 'attempts');

        return $attempts
            ->whereNotExists(function (Builder $newer) use ($type, $filters): void {
                $newer
                    ->selectRaw('1')
                    ->from('test_attempts as newer_attempts')
                    ->whereColumn('newer_attempts.user_id', 'attempts.user_id')
                    ->whereColumn('newer_attempts.module_id', 'attempts.module_id')
                    ->where('newer_attempts.type', $type)
                    ->whereNotNull('newer_attempts.completed_at')
                    ->where(function (Builder $query): void {
                        $query->whereColumn('newer_attempts.completed_at', '>', 'attempts.completed_at')
                            ->orWhere(function (Builder $tie): void {
                                $tie->whereColumn('newer_attempts.completed_at', '=', 'attempts.completed_at')
                                    ->whereColumn('newer_attempts.id', '>', 'attempts.id');
                            });
                    });

                $this->applyAttemptFilters($newer, $filters, 'newer_attempts');
            })
            ->select('attempts.user_id', 'attempts.module_id', 'attempts.score');
    }

    // FR-A08, AC-39: use inclusive completion dates consistently for every aggregate.
    private function applyAttemptFilters(Builder $query, array $filters, string $table): void
    {
        if (! empty($filters['module_id'])) {
            $query->where("{$table}.module_id", $filters['module_id']);
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate("{$table}.completed_at", '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate("{$table}.completed_at", '<=', $filters['date_to']);
        }
    }

    // FR-A08, FR-A09: keep table and export cell values identical.
    private function formatRow(object $row): array
    {
        $pre = $this->formatScore($row->pre_score);
        $post = $this->formatScore($row->post_score);

        return [
            'learner' => "{$row->learner_name} ({$row->learner_email})",
            'module' => $row->module_title,
            'pre' => $pre,
            'post' => $post,
            'difference' => $pre === '-' || $post === '-'
                ? '-'
                : number_format((float) $row->post_score - (float) $row->pre_score, 2, '.', ''),
            'units' => ((int) $row->completed_units).' / '.((int) $row->active_units),
        ];
    }

    // FR-A08, FR-A10: preserve displayed and exported score precision.
    private function formatScore(mixed $score): string
    {
        return $score === null ? '-' : number_format((float) $score, 2, '.', '');
    }
}
