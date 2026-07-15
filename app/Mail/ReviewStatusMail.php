<?php

namespace App\Mail;

use App\Models\Review;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReviewStatusMail extends Mailable
{
    use SerializesModels;

    public function __construct(public Review $review)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address($this->review->user->email, $this->review->user->name)],
            subject: __($this->review->status === 'approved' ? 'emails.review_approved_subject' : 'emails.review_rejected_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: $this->review->status === 'approved' ? 'emails.reviews.approved' : 'emails.reviews.rejected',
            with: ['review' => $this->review],
        );
    }
}
