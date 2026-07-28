<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\BusinessDay;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only viewer for the activity_logs table — already populated by
 * every meaningful admin mutation (create/update/delete/status-change)
 * across 14+ controllers via ActivityLog::record(), but until now there
 * was no way to actually browse it (2026-07 audit finding: a fully
 * write-only audit trail). Super-Admin-gated at the route level (see
 * routes/admin.php's super_admin middleware group), matching the
 * Roles & Permissions pages' read-only precedent — there's nothing to
 * create/edit/delete here, only to review.
 */
class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = ActivityLog::query()
            ->with('user:id,name,email')
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->input('user_id')))
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->input('action')))
            ->when($request->filled('subject_type'), fn ($q) => $q->where('subject_type', $request->input('subject_type')))
            ->when($request->filled('from'), fn ($q) => $q->where('created_at', '>=', BusinessDay::startOfDay($request->input('from'))))
            ->when($request->filled('to'), fn ($q) => $q->where('created_at', '<=', BusinessDay::endOfDay($request->input('to'))))
            ->latest('created_at')
            ->paginate(30)
            ->withQueryString();

        // Filter dropdowns are populated from what's actually in the
        // table, not a hardcoded list — a new action/subject type
        // automatically becomes filterable with zero changes here.
        $userIds = ActivityLog::query()->whereNotNull('user_id')->distinct()->pluck('user_id');
        $users = User::whereIn('id', $userIds)->orderBy('name')->get(['id', 'name']);
        $actions = ActivityLog::query()->select('action')->distinct()->orderBy('action')->pluck('action');
        $subjectTypes = ActivityLog::query()->select('subject_type')->distinct()->orderBy('subject_type')->pluck('subject_type');

        return view('admin.activity-log.index', compact('logs', 'users', 'actions', 'subjectTypes'));
    }
}
