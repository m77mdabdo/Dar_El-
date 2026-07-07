<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewContactMessage extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ContactMessage $message)
    {
        //
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_contact_message',
            'contact_message_id' => $this->message->id,
            'name' => $this->message->name,
        ];
    }
}
