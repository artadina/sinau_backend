<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreForumReplyRequest;
use App\Models\ForumPost;
use App\Models\ForumReport;
use App\Models\Module;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ForumController extends Controller
{
    // FR-A13, FR-A15: show module topics and the report inbox to Admin only.
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'module_id' => ['nullable', 'integer', 'exists:modules,id'],
        ], [
            'module_id.exists' => 'Modul yang dipilih tidak ditemukan.',
        ]);
        $modules = Module::query()->orderBy('title')->get(['id', 'title']);
        $selectedModuleId = $filters['module_id'] ?? $modules->first()?->id;
        $topics = $selectedModuleId
            ? ForumPost::query()
                ->with(['author', 'unit'])
                ->withCount('replies')
                ->where('module_id', $selectedModuleId)
                ->whereNull('parent_id')
                ->orderByDesc('created_at')
                ->paginate(15)
                ->withQueryString()
            : ForumPost::query()->whereRaw('1 = 0')->paginate(15);

        return view('admin.forum.index', [
            'title' => 'Forum & Laporan',
            'modules' => $modules,
            'selectedModuleId' => $selectedModuleId,
            'topics' => $topics,
        ]);
    }

    // FR-A13, AC-33: every post in the thread is rendered as escaped text.
    public function show(ForumPost $post): View|RedirectResponse
    {
        if ($post->parent_id !== null) {
            return redirect()->route('admin.forum.show', $this->rootTopic($post));
        }

        $posts = ForumPost::query()
            ->with(['author', 'unit'])
            ->where('module_id', $post->module_id)
            ->orderBy('created_at')
            ->get();
        $children = $posts->groupBy(fn (ForumPost $item) => $item->parent_id ?? 0);
        $topic = $posts->firstWhere('id', $post->id);

        if ($topic === null) {
            abort(404);
        }

        $attachReplies = function (ForumPost $parent) use (&$attachReplies, $children): void {
            $replies = $children->get($parent->id, new EloquentCollection);
            foreach ($replies as $reply) {
                $attachReplies($reply);
            }
            $parent->setRelation('replies', $replies);
        };
        $attachReplies($topic);

        return view('admin.forum.show', [
            'title' => 'Forum & Laporan',
            'topic' => $topic,
        ]);
    }

    // FR-A13, AC-33: all Admin replies are identified as Pengajar by their user role.
    public function reply(StoreForumReplyRequest $request, ForumPost $post): RedirectResponse
    {
        $reply = ForumPost::query()->create([
            'module_id' => $post->module_id,
            'unit_id' => $post->unit_id,
            'user_id' => $request->user()->id,
            'parent_id' => $post->id,
            'content' => $request->validated('content'),
        ]);

        return redirect()
            ->route('admin.forum.show', $this->rootTopic($reply))
            ->with('status', 'Balasan berhasil dikirim.');
    }

    // FR-A14, FR-A15, EC-12: close all reports before cascading deletion in one transaction.
    public function destroy(ForumPost $post): RedirectResponse
    {
        DB::transaction(function () use ($post): void {
            $postIds = $this->postTreeIds($post);
            ForumReport::query()
                ->whereIn('post_id', $postIds)
                ->update([
                    'status' => 'resolved',
                    'handled_by' => auth()->id(),
                    'handled_at' => now(),
                ]);

            $post->delete();
        });

        return redirect()
            ->route('admin.forum.index', ['module_id' => $post->module_id])
            ->with('status', 'Posting dan laporan terkait berhasil ditangani.');
    }

    // FR-A15, AC-34, AC-36, AC-37: pending reports are ordered before resolved history.
    public function reports(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:pending,resolved,all'],
        ], [
            'status.in' => 'Filter status laporan tidak valid.',
        ]);
        $status = $filters['status'] ?? 'all';
        $reports = ForumReport::query()
            ->with(['post.author', 'post.unit', 'post.module', 'reporter', 'handler'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.forum.reports', [
            'title' => 'Forum & Laporan',
            'reports' => $reports,
            'status' => $status,
        ]);
    }

    // FR-A15, AC-37: resolve without changing the related post.
    public function resolve(ForumReport $report): RedirectResponse
    {
        if ($report->status === 'pending') {
            $report->update([
                'status' => 'resolved',
                'handled_by' => auth()->id(),
                'handled_at' => now(),
            ]);
        }

        return redirect()
            ->route('admin.forum-reports.index')
            ->with('status', 'Laporan ditandai selesai tanpa menghapus konten.');
    }

    // EC-12: resolve nested replies too, since the FK cascade removes them with their parent.
    private function postTreeIds(ForumPost $post): Collection
    {
        $postsByParent = ForumPost::query()
            ->where('module_id', $post->module_id)
            ->lockForUpdate()
            ->get(['id', 'parent_id'])
            ->groupBy(fn (ForumPost $item) => $item->parent_id ?? 0);
        $ids = collect([$post->id]);
        $pending = collect([$post->id]);

        while ($pending->isNotEmpty()) {
            $parentId = $pending->shift();
            $childIds = $postsByParent->get($parentId, collect())->pluck('id');
            $ids = $ids->concat($childIds);
            $pending = $pending->concat($childIds);
        }

        return $ids->unique()->values();
    }

    // FR-A13: reply forms redirect back to the root topic regardless of reply depth.
    private function rootTopic(ForumPost $post): int
    {
        while ($post->parent_id !== null) {
            $post = $post->parent()->firstOrFail();
        }

        return $post->id;
    }
}
