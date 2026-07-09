<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Notifications\NewProductReviewSubmitted;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class ReviewController extends Controller
{
    public function __construct(protected ImageUploadService $imageUploader) {}

    public function store(StoreReviewRequest $request, Product $product): RedirectResponse
    {
        $review = $product->reviews()->create([
            'user_id' => $request->user()->id,
            'name' => $request->user()->name,
            'rating' => $request->integer('rating'),
            'title' => $request->input('title'),
            'comment' => $request->input('comment'),
            'status' => 'pending',
            'is_verified_purchase' => $this->isVerifiedPurchase($request->user()->id, $product->id),
        ]);

        $this->uploadPhotos($review, $request);

        Notification::send(User::admins(), new NewProductReviewSubmitted($review));

        return back()->with('status', __('reviews.submitted'));
    }

    public function update(UpdateReviewRequest $request, Review $review): RedirectResponse
    {
        $review->update([
            'rating' => $request->integer('rating'),
            'title' => $request->input('title'),
            'comment' => $request->input('comment'),
        ]);

        $this->uploadPhotos($review, $request);

        return back()->with('status', __('reviews.updated'));
    }

    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);

        $review->images->each->delete();
        $review->delete();

        return back()->with('status', __('reviews.deleted'));
    }

    public function markHelpful(Request $request, Review $review): RedirectResponse|JsonResponse
    {
        $review->increment('helpful_count');

        if ($request->wantsJson()) {
            return response()->json(['helpful_count' => $review->helpful_count]);
        }

        return back();
    }

    protected function isVerifiedPurchase(int $userId, int $productId): bool
    {
        return OrderItem::where('product_id', $productId)
            ->whereHas('order', fn ($q) => $q->where('user_id', $userId)->where('status', 'delivered'))
            ->exists();
    }

    protected function uploadPhotos(Review $review, Request $request): void
    {
        if (! $request->hasFile('photos')) {
            return;
        }

        $nextOrder = ($review->images()->max('sort_order') ?? -1) + 1;

        foreach ($request->file('photos') as $file) {
            $review->images()->create([
                'path' => $this->imageUploader->store($file, "reviews/{$review->id}"),
                'sort_order' => $nextOrder++,
            ]);
        }
    }
}
