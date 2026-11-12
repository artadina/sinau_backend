<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Module;
use App\Models\TestAttempt;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DemoHistorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminStepThreeTest extends TestCase
{
    use RefreshDatabase;

    // FR-A06, AC-38: search supports name/email, date filter works, and the page is read-only.
    public function test_admin_can_search_and_filter_learners_without_edit_or_delete_actions(): void
    {
        $admin = $this->admin();
        $matchByName = $this->learner([
            'name' => 'Nama Sasaran',
            'email' => 'name-match@example.test',
            'created_at' => now()->subDays(3),
        ]);
        $matchByEmail = $this->learner([
            'name' => 'Pengguna Lain',
            'email' => 'target-email@example.test',
            'created_at' => now()->subDays(2),
        ]);
        $outsideDate = $this->learner([
            'name' => 'Tidak Masuk Tanggal',
            'email' => 'outside@example.test',
            'created_at' => now()->subDays(20),
        ]);
        $deleted = $this->learner([
            'name' => 'Akun Terhapus',
            'email' => 'deleted@example.test',
        ]);
        $deleted->delete();

        $dateFilters = [
            'date_from' => now()->subDays(5)->toDateString(),
            'date_to' => now()->toDateString(),
        ];

        $this->actingAs($admin)
            ->get(route('admin.learners.index', ['search' => 'Sasaran'] + $dateFilters))
            ->assertOk()
            ->assertSee($matchByName->name)
            ->assertDontSee($matchByEmail->email)
            ->assertDontSee($outsideDate->name)
            ->assertDontSee($deleted->email)
            ->assertDontSee('Ubah role')
            ->assertDontSee('Edit')
            ->assertDontSee('Hapus');

        $this->get(route('admin.learners.index', ['search' => 'target-email'] + $dateFilters))
            ->assertOk()
            ->assertSee($matchByEmail->email)
            ->assertDontSee($matchByName->name);

        $this->assertFalse(Route::has('admin.learners.update'));
        $this->assertFalse(Route::has('admin.learners.destroy'));
    }

    // FR-A08: latest completed attempt wins, incomplete attempts are ignored, and inactive units are excluded.
    public function test_recap_uses_latest_scores_and_counts_only_active_completed_units(): void
    {
        $admin = $this->admin();
        $learner = $this->learner();
        $module = $this->module($admin, ['title' => 'Modul Rekap']);
        $activeUnit = $this->unit($module, 1, true);
        $secondActiveUnit = $this->unit($module, 2, true);
        $inactiveUnit = $this->unit($module, 3, false);

        $this->progress($learner, $activeUnit, true);
        $this->progress($learner, $secondActiveUnit, false);
        $this->progress($learner, $inactiveUnit, true);

        $this->attempt($learner, $module, 'pre', 40, now()->subDays(8));
        $this->attempt($learner, $module, 'pre', 65, now()->subDays(3));
        $this->attempt($learner, $module, 'post', 70, now()->subDays(4));
        $this->attempt($learner, $module, 'post', 80, now()->subDays(1));
        $this->attempt($learner, $module, 'post', 99, null);

        $this->actingAs($admin)
            ->get(route('admin.results.index'))
            ->assertOk()
            ->assertSee('65.00')
            ->assertSee('80.00')
            ->assertSee('15.00')
            ->assertSee('1 / 2');
    }

    // FR-A08: period filters select the latest completed scores inside the inclusive date range.
    public function test_recap_period_filters_completed_attempts_before_selecting_latest_score(): void
    {
        $admin = $this->admin();
        $learner = $this->learner();
        $module = $this->module($admin);
        $this->attempt($learner, $module, 'pre', 25, now()->subDays(20));
        $this->attempt($learner, $module, 'pre', 60, now()->subDays(4));
        $this->attempt($learner, $module, 'post', 90, now()->subDays(21));
        $this->attempt($learner, $module, 'post', 80, now()->subDays(2));

        $this->actingAs($admin)
            ->get(route('admin.results.index', [
                'module_id' => $module->id,
                'date_from' => now()->subDays(7)->toDateString(),
                'date_to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('60.00')
            ->assertSee('80.00')
            ->assertSee('20.00')
            ->assertDontSee('25.00')
            ->assertDontSee('90.00');
    }

    // FR-A09, AC-39: CSV values match the table with and without module/date filters.
    public function test_csv_matches_table_for_unfiltered_and_filtered_results(): void
    {
        $admin = $this->admin();
        $learner = $this->learner(['name' => 'Nama CSV', 'email' => 'csv@example.test']);
        $firstModule = $this->module($admin, ['title' => 'Modul Pertama']);
        $secondModule = $this->module($admin, ['title' => 'Modul Kedua']);
        $completedAt = now()->subDays(2);
        $this->attempt($learner, $firstModule, 'pre', 40, $completedAt);
        $this->attempt($learner, $firstModule, 'post', 65, $completedAt->copy()->addHour());
        $this->attempt($learner, $secondModule, 'pre', 50, $completedAt);

        $this->assertCsvMatchesTable($admin, [], [
            ['Nama CSV (csv@example.test)', 'Modul Kedua', '50.00', '-', '-', '0 / 0'],
            ['Nama CSV (csv@example.test)', 'Modul Pertama', '40.00', '65.00', '25.00', '0 / 0'],
        ]);
        $this->assertCsvMatchesTable($admin, [
            'module_id' => $firstModule->id,
            'date_from' => now()->subDays(4)->toDateString(),
            'date_to' => now()->toDateString(),
        ], [
            ['Nama CSV (csv@example.test)', 'Modul Pertama', '40.00', '65.00', '25.00', '0 / 0'],
        ]);
    }

    // FR-A08, FR-A09: CSV includes every filtered result even when the table is paginated.
    public function test_csv_exports_all_filtered_rows_across_pages(): void
    {
        $admin = $this->admin();
        $module = $this->module($admin);

        for ($number = 1; $number <= 16; $number++) {
            $learner = $this->learner([
                'name' => sprintf('Learner %02d', $number),
                'email' => sprintf('page-%02d@example.test', $number),
            ]);
            $this->attempt($learner, $module, 'pre', 50, now()->subDay());
        }

        $page = $this->actingAs($admin)
            ->get(route('admin.results.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('Learner 16')
            ->assertDontSee('Learner 01');

        $csv = $this->get(route('admin.results.export'))->streamedContent();
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, substr($csv, 3));
        rewind($stream);
        fgetcsv($stream);

        $rows = [];
        while (($row = fgetcsv($stream)) !== false) {
            $rows[] = $row;
        }
        fclose($stream);

        $this->assertCount(16, $rows);
        $this->assertSame(16, $page->viewData('rows')->total());
    }

    // FR-A10, FR-A03, FR-A05, FR-A11, AC-40: dashboard avoids duplicate content-management sidebar links.
    public function test_dashboard_counts_completed_tests_and_averages_paired_latest_scores(): void
    {
        $admin = $this->admin();
        $firstLearner = $this->learner();
        $secondLearner = $this->learner();
        $module = $this->module($admin, ['title' => 'Modul Dashboard']);
        $this->attempt($firstLearner, $module, 'pre', 40, now()->subDays(3));
        $this->attempt($firstLearner, $module, 'post', 80, now()->subDays(2));
        $this->attempt($secondLearner, $module, 'pre', 100, now()->subDay());
        $this->attempt($secondLearner, $module, 'post', 100, null);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Jumlah Learner')
            ->assertSee('2')
            ->assertSee('Jumlah Modul')
            ->assertSee('Tes Selesai')
            ->assertSee('3')
            ->assertSee('Modul Dashboard')
            ->assertSee('40.00')
            ->assertSee('80.00')
            ->assertSee('Kelola Modul')
            ->assertSee('Kelola Soal')
            ->assertDontSee('Kelola Unit & Kuis')
            ->assertDontSee('Kelola Bank Soal');
    }

    // FR-S05, AB-09, AC-10: only Admin can access any Step 3 route.
    public function test_superadmin_and_learner_get_forbidden_on_all_step_three_routes(): void
    {
        $routes = [
            route('admin.dashboard'),
            route('admin.learners.index'),
            route('admin.results.index'),
            route('admin.results.export'),
        ];

        foreach ([Role::Superadmin, Role::Learner] as $role) {
            $user = User::factory()->create([
                'role' => $role->value,
                'is_temp_password' => false,
            ]);

            foreach ($routes as $url) {
                $this->actingAs($user)->get($url)->assertForbidden();
            }
        }
    }

    // FR-A09: CSV cells starting with formula markers are prefixed with a single quote.
    public function test_csv_has_utf8_bom_and_neutralizes_formula_cells(): void
    {
        $admin = $this->admin();
        $learner = $this->learner(['name' => '=FORMULA']);
        $module = $this->module($admin, ['title' => '+MODUL']);
        $this->attempt($learner, $module, 'pre', 75, now()->subDay());
        $this->attempt($learner, $module, 'post', 25, now());

        $response = $this->actingAs($admin)->get(route('admin.results.export'));
        $content = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString("'=FORMULA", $content);
        $this->assertStringContainsString("'+MODUL", $content);
        $this->assertStringContainsString("'-50.00", $content);
    }

    // FR-A06, FR-A08, FR-A10: the demo history seeder is safe to run repeatedly and keeps answers consistent.
    public function test_demo_history_seeder_is_repeatable_and_answers_match_saved_scores(): void
    {
        $this->admin();
        $seeder = new DemoHistorySeeder;
        $seeder->run();
        $attemptCount = TestAttempt::query()->count();
        $answerCount = DB::table('test_attempt_answers')->count();
        $seeder->run();

        $this->assertSame(11, $attemptCount);
        $this->assertSame($attemptCount, TestAttempt::query()->count());
        $this->assertSame($answerCount, DB::table('test_attempt_answers')->count());

        $attempt = TestAttempt::query()->firstOrFail();
        $answerCountForAttempt = DB::table('test_attempt_answers')
            ->where('attempt_id', $attempt->id)
            ->count();
        $correctCount = DB::table('test_attempt_answers')
            ->where('attempt_id', $attempt->id)
            ->where('is_correct', true)
            ->count();
        $this->assertSame(4, $answerCountForAttempt);
        $this->assertEquals((float) $attempt->score, ($correctCount / $answerCountForAttempt) * 100);
        $this->assertSame(7, User::query()->where('role', Role::Learner->value)->count());
    }

    private function assertCsvMatchesTable(User $admin, array $filters, array $expectedRows): void
    {
        $table = $this->actingAs($admin)->get(route('admin.results.index', $filters))->assertOk();
        foreach ($expectedRows as $expectedRow) {
            foreach ($expectedRow as $cell) {
                $table->assertSee(e($cell), false);
            }
        }

        $csvResponse = $this->actingAs($admin)->get(route('admin.results.export', $filters))->assertOk();
        $content = $csvResponse->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, substr($content, 3));
        rewind($stream);
        $this->assertSame(
            ['Pengguna', 'Modul', 'Skor pre', 'Skor post', 'Selisih', 'Unit selesai'],
            fgetcsv($stream),
        );
        foreach ($expectedRows as $expectedRow) {
            $safeRow = array_map(
                fn (string $cell): string => preg_match('/^[=+\-@]/', $cell) === 1 ? "'".$cell : $cell,
                $expectedRow,
            );
            $this->assertSame($safeRow, fgetcsv($stream));
        }
        $this->assertFalse(fgetcsv($stream));
        fclose($stream);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => Role::Admin->value,
            'is_temp_password' => false,
        ]);
    }

    private function learner(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => Role::Learner->value,
            'is_temp_password' => false,
        ], $attributes));
    }

    private function module(User $admin, array $attributes = []): Module
    {
        return Module::query()->create(array_merge([
            'title' => 'Modul Uji',
            'description' => '',
            'status' => 'draft',
            'created_by' => $admin->id,
        ], $attributes));
    }

    private function unit(Module $module, int $sequence, bool $isActive): Unit
    {
        return Unit::query()->create([
            'module_id' => $module->id,
            'title' => "Unit {$sequence}",
            'learning_objective' => 'Tujuan unit.',
            'video_url' => 'https://example.test/video.mp4',
            'reading_content' => '',
            'sequence_order' => $sequence,
            'duration' => 60,
            'is_active' => $isActive,
        ]);
    }

    private function progress(User $learner, Unit $unit, bool $completed): void
    {
        DB::table('learner_unit_progress')->insert([
            'user_id' => $learner->id,
            'unit_id' => $unit->id,
            'last_video_pos' => 30,
            'is_completed' => $completed,
            'updated_at' => now(),
        ]);
    }

    private function attempt(User $learner, Module $module, string $type, float $score, ?Carbon $completedAt): TestAttempt
    {
        return TestAttempt::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'type' => $type,
            'score' => $score,
            'completed_at' => $completedAt,
        ]);
    }
}
