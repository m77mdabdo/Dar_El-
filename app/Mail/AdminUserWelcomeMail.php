<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to a newly created staff account (super_admin/admin/employee) when
 * Super Admin ticks "send welcome email" on the create form. Dispatched
 * via Mail::to($user->email)->queue(...) (see UserController::store()),
 * so — per this app's established convention — no `to:` is set on the
 * envelope; Mail::to() handles addressing.
 */
class AdminUserWelcomeMail extends Mailable
{
    use SerializesModels;

    public function __construct(public User $user, public string $temporaryPassword)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.admin_user_welcome_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.staff-welcome',
            with: [
                'user' => $this->user,
                'temporaryPassword' => $this->temporaryPassword,
                'roleLabel' => __('users.role_'.$this->user->getRoleNames()->first()),
            ],
        );
    }
}
