<?php

namespace App\Notifications;

use App\Mail\NewCustomerRegisteredMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewCustomerRegistered extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $customer)
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

    public function toMail(object $notifiable): NewCustomerRegisteredMail
    {
        return new NewCustomerRegisteredMail($this->customer, $notifiable);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_customer',
            'customer_id' => $this->customer->id,
            'customer_name' => $this->customer->name,
        ];
    }
}
