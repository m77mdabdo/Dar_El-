<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\NewContactMessage;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;

class ContactController extends Controller
{
    public function show()
    {
        $heroImage = Setting::get('contact_hero_image', 'https://images.unsplash.com/photo-1644827561353-8a1d9f4bec8e?w=1600&q=80&auto=format&fit=crop');

        return view('contact.show', compact('heroImage'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        $message = ContactMessage::create($validated);

        Notification::send(User::admins(), new NewContactMessage($message));

        return back()->with('status', __('Your message has been sent. We will get back to you soon.'));
    }
}
