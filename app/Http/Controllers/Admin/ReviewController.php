<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\Review;
use App\Notifications\ReviewStatusUpdated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Review::class);

        $reviews = Review::with(['product', 'user'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->rating, fn ($q) => $q->where('rating', (int) $request->rating))
            ->when($request->product_id, fn ($q) => $q->where('product_id', $request->product_id))
            ->when($request->verified === '1', fn ($q) => $q->where('is_verified_purchase', true))
            ->when($request->verified === '0', fn ($q) => $q->where('is_verified_purchase', false))
            ->when($request->date_from, fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->when($request->search, fn ($q) => $q->where(fn ($sub) => $sub
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('comment', 'like', "%{$request->search}%")
                ->orWhereHas('product', fn ($p) => $p->where('name_en', 'like', "%{$request->search}%"))
            ))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stats = $this->stats();
        $charts = $this->charts();
        $products = Product::orderBy('name_en')->get(['id', 'name_en']);

        return view('admin.reviews.index', compact('reviews', 'stats', 'charts', 'products'));
    }

    public function show(Review $review)
    {
        $this->authorize('view', $review);

        $review->load(['product', 'user', 'images', 'approvedBy', 'rejectedBy']);

        return view('admin.reviews.show', compact('review'));
    }

    public function approve(Review $review): RedirectResponse
    {
        $review->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
            'rejected_at' => null,
            'rejected_by' => null,
            'rejection_reason' => null,
        ]);

        $review->user?->notify(new ReviewStatusUpdated($review));

        ActivityLog::record('approved', $review, "Approved review #{$review->id}");

        return back()->with('status', __('reviews.approved_status'));
    }

    public function reject(Request $request, Review $review): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $review->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by' => auth()->id(),
            'rejection_reason' => $validated['reason'] ?? null,
            'approved_at' => null,
            'approved_by' => null,
        ]);

        $review->user?->notify(new ReviewStatusUpdated($review));

        ActivityLog::record('rejected', $review, "Rejected review #{$review->id}");

        return back()->with('status', __('reviews.rejected_status'));
    }

    public function feature(Review $review): RedirectResponse
    {
        $review->update(['is_featured' => true]);

        ActivityLog::record('updated', $review, "Featured review #{$review->id}");

        return back()->with('status', __('reviews.featured_status'));
    }

    public function unfeature(Review $review): RedirectResponse
    {
        $review->update(['is_featured' => false]);

        ActivityLog::record('updated', $review, "Unfeatured review #{$review->id}");

        return back()->with('status', __('reviews.unfeatured_status'));
    }

    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);

        $review->delete();

        ActivityLog::record('deleted', $review, "Deleted review #{$review->id}");

        return redirect()->route('admin.reviews.index')->with('status', __('reviews.deleted'));
    }

    protected function stats(): array
    {
        return [
            'total' => Review::count(),
            'pending' => Review::where('status', 'pending')->count(),
            'approved' => Review::where('status', 'approved')->count(),
            'rejected' => Review::where('status', 'rejected')->count(),
            'average_rating' => round(Review::where('status', 'approved')->avg('rating') ?? 0, 1),
            'today' => Review::whereDate('created_at', today())->count(),
            'this_month' => Review::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->count(),
        ];
    }

    protected function charts(): array
    {
        $byRating = Review::select('rating', DB::raw('COUNT(*) as count'))->groupBy('rating')->pluck('count', 'rating');
        $byStatus = Review::select('status', DB::raw('COUNT(*) as count'))->groupBy('status')->pluck('count', 'status');

        $topReviewed = Review::select('product_id', DB::raw('COUNT(*) as count'))
            ->where('status', 'approved')
            ->groupBy('product_id')
            ->orderByDesc('count')
            ->take(5)
            ->with('product:id,name_en,name_ar')
            ->get()
            ->filter(fn ($row) => $row->product !== null)
            ->values();

        $ratingByProduct = Review::select('product_id', DB::raw('AVG(rating) as avg_rating'))
            ->where('status', 'approved')
            ->groupBy('product_id')
            ->with('product:id,name_en,name_ar')
            ->get()
            ->filter(fn ($row) => $row->product !== null)
            ->values();

        $highestRated = $ratingByProduct->sortByDesc('avg_rating')->take(5)->values();
        $lowestRated = $ratingByProduct->sortBy('avg_rating')->take(5)->values();

        return compact('byRating', 'byStatus', 'topReviewed', 'highestRated', 'lowestRated');
    }
}
