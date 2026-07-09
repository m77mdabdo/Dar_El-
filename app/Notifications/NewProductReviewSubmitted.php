<?php

namespace App\Notifications;

use App\Mail\NewProductReviewSubmittedMail;
use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewProductReviewSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Review $review)
    {
        //
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): NewProductReviewSubmittedMail
    {
        return new NewProductReviewSubmittedMail($this->review, $notifiable);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_product_review',
            'review_id' => $this->review->id,
            'product_name' => $this->review->product->name_en,
            'rating' => $this->review->rating,
        ];
    }
}
