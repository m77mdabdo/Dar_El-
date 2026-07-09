<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * Template only — not wired to any trigger yet. No wishlist-reminder job
 * exists in this app today; ready for whenever that scheduled feature is
 * built (mirroring the abandoned-cart-reminder pattern).
 */
class WishlistReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public Collection $wishlists)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address($this->user->email, $this->user->name)],
            subject: __('emails.wishlist_reminder_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.wishlist.reminder',
            with: ['user' => $this->user, 'wishlists' => $this->wishlists],
        );
    }
}
