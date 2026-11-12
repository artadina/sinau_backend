<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\ForumPost;
use App\Models\ForumReport;
use App\Models\Module;
use App\Models\User;
use Database\Seeders\DemoForumSeeder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class AdminForumModerationTest extends TestCase
{
    use RefreshDatabase;

    // FR-A13, AC-33: Admin can read module topics and reply to both topics and replies as Pengajar.
    public function test_admin_can_browse_topics_and_reply_to_a_reply_as_pengajar(): void
    {
        $admin = $this->admin();
        $learner = $this->learner();
        $module = $this->module($admin);
        $topic = $this->createForumPost($module, $learner, 'Topik diskusi');
        $reply = $this->createForumPost($module, $learner, 'Balasan learner', $topic);

        $this->actingAs($admin)
            ->get(route('admin.forum.index', ['module_id' => $module->id]))
            ->assertOk()
            ->assertSee('Topik diskusi')
            ->assertSee('1 balasan');

        $this->post(route('admin.forum.replies.store', $reply), ['content' => 'Balasan dari Pengajar.'])
            ->assertRedirect(route('admin.forum.show', $topic));

        $this->get(route('admin.forum.show', $topic))
            ->assertOk()
            ->assertSee('Balasan learner')
            ->assertSee('Balasan dari Pengajar.')
            ->assertSee('Pengajar')
            ->assertSee("return confirm('Hapus topik beserta seluruh balasan dan tutup laporan terkait?');", false);
        $this->get(route('admin.forum.show', $reply))
            ->assertRedirect(route('admin.forum.show', $topic));
    }

    // FR-A13, AB-16: reply input is required and limited to 1000 characters with Indonesian errors.
    public function test_reply_form_validates_required_and_maximum_length(): void
    {
        $admin = $this->admin();
        $module = $this->module($admin);
        $topic = $this->createForumPost($module, $this->learner(), 'Topik');

        $this->actingAs($admin)
            ->post(route('admin.forum.replies.store', $topic), ['content' => ''])
            ->assertSessionHasErrors(['content' => 'Balasan wajib diisi.']);

        $this->post(route('admin.forum.replies.store', $topic), ['content' => str_repeat('a', 1001)])
            ->assertSessionHasErrors(['content' => 'Balasan maksimal 1000 karakter.']);
    }

    // FR-A15, AC-34: pending report count and full report details appear while the reported post remains visible.
    public function test_pending_reports_show_in_sidebar_and_report_list_without_hiding_content(): void
    {
        $admin = $this->admin();
        $learner = $this->learner();
        $module = $this->module($admin);
        $topic = $this->createForumPost($module, $learner, '<script>alert(1)</script>');
        $report = $this->report($topic, $this->learner(), 'Mohon tinjau isi posting ini.');

        $this->actingAs($admin)
            ->get(route('admin.forum.index', ['module_id' => $module->id]))
            ->assertOk()
            ->assertSee('1 laporan menunggu')
            ->assertSee(e('<script>alert(1)</script>'), false)
            ->assertDontSee('<script>alert(1)</script>', false);

        $this->get(route('admin.forum-reports.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('Mohon tinjau isi posting ini.')
            ->assertSee($report->reporter->name)
            ->assertSee(e('<script>alert(1)</script>'), false)
            ->assertDontSee('<script>alert(1)</script>', false);
        $this->assertDatabaseHas('forum_posts', ['id' => $topic->id, 'content' => '<script>alert(1)</script>']);
    }

    // FR-A15, AC-37: resolving a report records the Admin and timestamp but leaves its post intact.
    public function test_admin_can_resolve_a_report_without_deleting_its_post(): void
    {
        $admin = $this->admin();
        $module = $this->module($admin);
        $topic = $this->createForumPost($module, $this->learner(), 'Posting wajar');
        $report = $this->report($topic, $this->learner(), 'Alasan laporan');

        $this->actingAs($admin)
            ->put(route('admin.forum-reports.resolve', $report))
            ->assertRedirect(route('admin.forum-reports.index'));

        $this->assertDatabaseHas('forum_posts', ['id' => $topic->id]);
        $this->assertDatabaseHas('forum_reports', [
            'id' => $report->id,
            'post_id' => $topic->id,
            'status' => 'resolved',
            'handled_by' => $admin->id,
        ]);
        $this->assertNotNull($report->fresh()->handled_at);
    }

    // FR-A15: the report status filter separates pending and resolved while keeping pending first in all.
    public function test_report_filters_show_matching_status_and_order_pending_before_resolved(): void
    {
        $admin = $this->admin();
        $module = $this->module($admin);
        $pendingPost = $this->createForumPost($module, $this->learner(), 'Laporan pending');
        $resolvedPost = $this->createForumPost($module, $this->learner(), 'Laporan resolved');
        $pending = $this->report($pendingPost, $this->learner(), 'Alasan pending');
        $resolved = $this->report($resolvedPost, $this->learner(), 'Alasan resolved');
        $resolved->update(['status' => 'resolved']);

        $allReports = $this->actingAs($admin)
            ->get(route('admin.forum-reports.index', ['status' => 'all']))
            ->assertOk()
            ->viewData('reports');
        $this->assertSame([$pending->id, $resolved->id], $allReports->getCollection()->pluck('id')->all());

        $this->get(route('admin.forum-reports.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('Laporan pending')
            ->assertDontSee('Laporan resolved');
        $this->get(route('admin.forum-reports.index', ['status' => 'resolved']))
            ->assertOk()
            ->assertSee('Laporan resolved')
            ->assertDontSee('Laporan pending');
    }

    // FR-A14, FR-A15, EC-12, AC-36: deleting a topic cascades replies and resolves every related report.
    public function test_deleting_topic_removes_nested_replies_and_preserves_resolved_report_history(): void
    {
        $admin = $this->admin();
        $module = $this->module($admin);
        $topic = $this->createForumPost($module, $this->learner(), 'Topik yang dihapus');
        $reply = $this->createForumPost($module, $this->learner(), 'Balasan yang dihapus', $topic);
        $nestedReply = $this->createForumPost($module, $this->learner(), 'Balasan turunan yang dihapus', $reply);
        $unrelatedTopic = $this->createForumPost($module, $this->learner(), 'Topik lain');
        $topicReport = $this->report($topic, $this->learner(), 'Laporan topik');
        $replyReport = $this->report($reply, $this->learner(), 'Laporan balasan');
        $nestedReport = $this->report($nestedReply, $this->learner(), 'Laporan balasan turunan');

        $this->actingAs($admin)
            ->delete(route('admin.forum.posts.destroy', $topic))
            ->assertRedirect(route('admin.forum.index', ['module_id' => $module->id]));

        $this->assertDatabaseMissing('forum_posts', ['id' => $topic->id]);
        $this->assertDatabaseMissing('forum_posts', ['id' => $reply->id]);
        $this->assertDatabaseMissing('forum_posts', ['id' => $nestedReply->id]);
        $this->assertDatabaseHas('forum_posts', ['id' => $unrelatedTopic->id]);
        foreach ([$topicReport, $replyReport, $nestedReport] as $report) {
            $this->assertDatabaseHas('forum_reports', [
                'id' => $report->id,
                'post_id' => null,
                'status' => 'resolved',
                'handled_by' => $admin->id,
            ]);
            $this->assertNotNull($report->fresh()->handled_at);
        }

        $this->get(route('admin.forum-reports.index', ['status' => 'resolved']))
            ->assertOk()
            ->assertSee('Konten sudah dihapus')
            ->assertSee('Laporan balasan');
    }

    // FR-A14, FR-A15: deleting a single reply closes its report and keeps its topic intact.
    public function test_deleting_a_reply_preserves_its_topic_and_resolves_its_report(): void
    {
        $admin = $this->admin();
        $module = $this->module($admin);
        $topic = $this->createForumPost($module, $this->learner(), 'Topik tetap ada');
        $reply = $this->createForumPost($module, $this->learner(), 'Balasan dihapus', $topic);
        $report = $this->report($reply, $this->learner(), 'Alasan laporan balasan');

        $this->actingAs($admin)
            ->delete(route('admin.forum.posts.destroy', $reply))
            ->assertRedirect(route('admin.forum.index', ['module_id' => $module->id]));

        $this->assertDatabaseHas('forum_posts', ['id' => $topic->id]);
        $this->assertDatabaseMissing('forum_posts', ['id' => $reply->id]);
        $this->assertDatabaseHas('forum_reports', [
            'id' => $report->id,
            'post_id' => null,
            'status' => 'resolved',
            'handled_by' => $admin->id,
        ]);
    }

    // EC-12: an exception after reports are updated rolls back both report changes and post deletion.
    public function test_topic_deletion_rolls_back_all_changes_when_processing_fails_mid_transaction(): void
    {
        $admin = $this->admin();
        $module = $this->module($admin);
        $topic = $this->createForumPost($module, $this->learner(), 'Topik atomik');
        $reply = $this->createForumPost($module, $this->learner(), 'Balasan atomik', $topic);
        $report = $this->report($reply, $this->learner(), 'Laporan atomik');
        $failNextReportUpdate = true;

        DB::listen(function (QueryExecuted $query) use (&$failNextReportUpdate): void {
            if ($failNextReportUpdate && str_starts_with(strtolower($query->sql), 'update "forum_reports"')) {
                $failNextReportUpdate = false;
                throw new RuntimeException('Simulasi kegagalan setelah laporan diperbarui.');
            }
        });
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($admin)->delete(route('admin.forum.posts.destroy', $topic));
            $this->fail('Kegagalan transaksi seharusnya diteruskan.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulasi kegagalan setelah laporan diperbarui.', $exception->getMessage());
        }

        $this->assertDatabaseHas('forum_posts', ['id' => $topic->id]);
        $this->assertDatabaseHas('forum_posts', ['id' => $reply->id]);
        $this->assertDatabaseHas('forum_reports', [
            'id' => $report->id,
            'post_id' => $reply->id,
            'status' => 'pending',
            'handled_by' => null,
            'handled_at' => null,
        ]);
    }

    // FR-S05, FR-SA07, AC-10: forum, reports, replies, deletion, and resolution are Admin-only.
    public function test_superadmin_and_learner_receive_forbidden_for_forum_and_moderation_routes(): void
    {
        $admin = $this->admin();
        $module = $this->module($admin);
        $topic = $this->createForumPost($module, $this->learner(), 'Topik');
        $report = $this->report($topic, $this->learner(), 'Alasan');
        $urls = [
            ['GET', route('admin.forum.index')],
            ['GET', route('admin.forum.show', $topic)],
            ['GET', route('admin.forum-reports.index')],
            ['POST', route('admin.forum.replies.store', $topic)],
            ['DELETE', route('admin.forum.posts.destroy', $topic)],
            ['PUT', route('admin.forum-reports.resolve', $report)],
        ];

        foreach ([Role::Superadmin, Role::Learner] as $role) {
            $user = User::factory()->create([
                'role' => $role->value,
                'is_temp_password' => false,
            ]);

            foreach ($urls as [$method, $url]) {
                $this->actingAs($user)
                    ->call($method, $url, ['content' => 'Balasan'])
                    ->assertForbidden();
            }
        }
    }

    // FR-A15, EC-12: DemoForumSeeder creates report cases for a topic and reply and is repeatable.
    public function test_demo_forum_seeder_is_repeatable(): void
    {
        $seeder = new DemoForumSeeder;
        $seeder->run();
        $postCount = ForumPost::query()->count();
        $reportCount = ForumReport::query()->count();
        $seeder->run();

        $this->assertSame($postCount, ForumPost::query()->count());
        $this->assertSame($reportCount, ForumReport::query()->count());
        $this->assertSame(2, ForumReport::query()->where('status', 'pending')->count());
        $this->assertSame(2, Module::query()->count());
        $this->assertSame(1, ForumPost::query()->whereHas('author', fn ($query) => $query->where('role', Role::Admin->value))->count());
        $this->assertSame(4, ForumPost::query()->whereNull('parent_id')->count());
        $this->assertSame(1, ForumReport::query()
            ->with('post.replies')
            ->get()
            ->filter(fn (ForumReport $report): bool => $report->post?->parent_id === null && $report->post->replies->isNotEmpty())
            ->count());
        $this->assertSame(1, ForumReport::query()->whereHas('post', fn ($query) => $query->whereNotNull('parent_id'))->count());
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => Role::Admin->value,
            'is_temp_password' => false,
        ]);
    }

    private function learner(): User
    {
        return User::factory()->create([
            'role' => Role::Learner->value,
            'is_temp_password' => false,
        ]);
    }

    private function module(User $admin): Module
    {
        return Module::query()->create([
            'title' => 'Modul Forum',
            'description' => 'Modul untuk pengujian forum.',
            'status' => 'published',
            'created_by' => $admin->id,
        ]);
    }

    private function createForumPost(Module $module, User $author, string $content, ?ForumPost $parent = null): ForumPost
    {
        return ForumPost::query()->create([
            'module_id' => $module->id,
            'user_id' => $author->id,
            'parent_id' => $parent?->id,
            'content' => $content,
        ]);
    }

    private function report(ForumPost $post, User $reporter, string $reason): ForumReport
    {
        return ForumReport::query()->create([
            'post_id' => $post->id,
            'reported_by' => $reporter->id,
            'reason' => $reason,
            'status' => 'pending',
        ]);
    }
}
