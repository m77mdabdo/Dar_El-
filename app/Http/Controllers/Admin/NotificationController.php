<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()->paginate(20);

        return view('admin.notifications.index', compact('notifications'));
    }

    public function markRead(Request $request, DatabaseNotification $notification): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        abort_unless($notification->notifiable_id === $request->user()->id, 404);

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        $unreadCount = $request->user()->unreadNotifications()->count();

        if ($request->wantsJson()) {
            return response()->json(['unread_count' => $unreadCount]);
        }

        return back();
    }

    public function markAllRead(Request $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        if ($request->wantsJson()) {
            return response()->json(['unread_count' => 0]);
        }

        return back()->with('status', __('admin.notifications.marked_all_read'));
    }
}
