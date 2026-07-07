<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Notifications\NewsletterSubscribed;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class NewsletterController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', Rule::unique(NewsletterSubscriber::class, 'email')],
        ]);

        $subscriber = NewsletterSubscriber::create($validated);

        Notification::send(User::admins(), new NewsletterSubscribed($subscriber));

        return back()->with('status', 'Subscribed! Thanks for joining our newsletter.');
    }
}
