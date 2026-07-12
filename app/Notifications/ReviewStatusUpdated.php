<?php

namespace App\Notifications;

use App\Mail\ReviewStatusMail;
use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ReviewStatusUpdated extends Notification implements ShouldQueue
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

    public function toMail(object $notifiable): ReviewStatusMail
    {
        return new ReviewStatusMail($this->review);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'review_'.$this->review->status,
            'review_id' => $this->review->id,
            'product_name' => $this->review->product->name_en,
            'product_name_ar' => $this->review->product->name_ar,
            'product_name_en' => $this->review->product->name_en,
            'status' => $this->review->status,
        ];
    }
}
