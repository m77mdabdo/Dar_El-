<?php

namespace App\Mail;

use App\Models\Review;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewProductReviewSubmittedMail extends Mailable
{
    use SerializesModels;

    public function __construct(public Review $review, public User $admin)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address($this->admin->email, $this->admin->name)],
            subject: __('emails.admin_new_review_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.new-review',
            with: ['review' => $this->review],
        );
    }
}
