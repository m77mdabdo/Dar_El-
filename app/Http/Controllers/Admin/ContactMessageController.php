<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;

class ContactMessageController extends Controller
{
    public function index()
    {
        $messages = ContactMessage::latest()->paginate(20);

        return view('admin.contact-messages.index', compact('messages'));
    }

    public function markRead(ContactMessage $contactMessage): RedirectResponse
    {
        $contactMessage->update(['is_read' => true]);

        return back()->with('status', 'Marked as read.');
    }
}
