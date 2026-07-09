<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BlogComment;
use App\Models\BlogPost;
use App\Notifications\BlogCommentStatusUpdated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BlogCommentController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', BlogComment::class);

        $comments = BlogComment::with(['blogPost', 'user'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->blog_post_id, fn ($q) => $q->where('blog_post_id', $request->blog_post_id))
            ->when($request->date_from, fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->when($request->search, fn ($q) => $q->where(fn ($sub) => $sub
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('comment', 'like', "%{$request->search}%")
                ->orWhereHas('blogPost', fn ($p) => $p->where('title_en', 'like', "%{$request->search}%"))
            ))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stats = $this->stats();
        $charts = $this->charts();
        $posts = BlogPost::orderBy('title_en')->get(['id', 'title_en']);

        return view('admin.blog-comments.index', compact('comments', 'stats', 'charts', 'posts'));
    }

    public function show(BlogComment $comment)
    {
        $this->authorize('view', $comment);

        $comment->load(['blogPost', 'user', 'approvedBy', 'rejectedBy']);

        return view('admin.blog-comments.show', compact('comment'));
    }

    public function approve(BlogComment $comment): RedirectResponse
    {
        $comment->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
            'rejected_at' => null,
            'rejected_by' => null,
            'rejection_reason' => null,
        ]);

        $comment->user?->notify(new BlogCommentStatusUpdated($comment));

        ActivityLog::record('approved', $comment, "Approved blog comment #{$comment->id}");

        return back()->with('status', __('blog_comments.approved_status'));
    }

    public function reject(Request $request, BlogComment $comment): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $comment->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by' => auth()->id(),
            'rejection_reason' => $validated['reason'] ?? null,
            'approved_at' => null,
            'approved_by' => null,
        ]);

        $comment->user?->notify(new BlogCommentStatusUpdated($comment));

        ActivityLog::record('rejected', $comment, "Rejected blog comment #{$comment->id}");

        return back()->with('status', __('blog_comments.rejected_status'));
    }

    public function destroy(BlogComment $comment): RedirectResponse
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        ActivityLog::record('deleted', $comment, "Deleted blog comment #{$comment->id}");

        return redirect()->route('admin.blog-comments.index')->with('status', __('blog_comments.deleted'));
    }

    protected function stats(): array
    {
        return [
            'total' => BlogComment::count(),
            'pending' => BlogComment::where('status', 'pending')->count(),
            'approved' => BlogComment::where('status', 'approved')->count(),
            'rejected' => BlogComment::where('status', 'rejected')->count(),
            'today' => BlogComment::whereDate('created_at', today())->count(),
            'this_month' => BlogComment::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->count(),
        ];
    }

    protected function charts(): array
    {
        $byStatus = BlogComment::select('status', DB::raw('COUNT(*) as count'))->groupBy('status')->pluck('count', 'status');

        $since = now()->subDays(13)->startOfDay();
        $daily = BlogComment::select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', $since)
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $labels = [];
        $series = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $key = $date->format('Y-m-d');
            $labels[] = $date->format('M j');
            $series[] = (int) ($daily[$key]->count ?? 0);
        }

        $topCommented = BlogComment::select('blog_post_id', DB::raw('COUNT(*) as count'))
            ->where('status', 'approved')
            ->groupBy('blog_post_id')
            ->orderByDesc('count')
            ->take(5)
            ->with('blogPost:id,title_en,title_ar')
            ->get()
            ->filter(fn ($row) => $row->blogPost !== null)
            ->values();

        return compact('byStatus', 'labels', 'series', 'topCommented');
    }
}
