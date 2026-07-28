@extends('admin.layout')

@section('title', __('activity_log.title'))

@section('content')
    <p class="dj-admin-hint mb-4">{{ __('activity_log.subtitle') }}</p>

    @php
        // __() returns the raw key itself (not null) when a translation is
        // missing, so a plain ?? fallback never actually triggers — this
        // explicitly checks first, falling back to the raw action/subject
        // name for anything not yet in lang/{locale}/activity_log.php
        // (a future new ActivityLog::record() action/model still renders
        // something readable instead of crashing or showing a raw key).
        $djLabel = fn (string $key, string $fallback) => \Illuminate\Support\Facades\Lang::has($key) ? __($key) : $fallback;
    @endphp

    <form method="GET" class="flex flex-wrap items-end gap-2 mb-6">
        <select name="user_id" onchange="this.form.submit()" class="dj-admin-input w-auto">
            <option value="">{{ __('activity_log.all_users') }}</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" {{ (string) request('user_id') === (string) $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
            @endforeach
        </select>
        <select name="action" onchange="this.form.submit()" class="dj-admin-input w-auto">
            <option value="">{{ __('activity_log.all_actions') }}</option>
            @foreach ($actions as $action)
                <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>{{ $djLabel('activity_log.actions.'.$action, $action) }}</option>
            @endforeach
        </select>
        <select name="subject_type" onchange="this.form.submit()" class="dj-admin-input w-auto">
            <option value="">{{ __('activity_log.all_subject_types') }}</option>
            @foreach ($subjectTypes as $subjectType)
                @php $djShortName = class_basename($subjectType); @endphp
                <option value="{{ $subjectType }}" {{ request('subject_type') === $subjectType ? 'selected' : '' }}>{{ $djLabel('activity_log.subjects.'.$djShortName, $djShortName) }}</option>
            @endforeach
        </select>
        <div>
            <label class="dj-admin-label">{{ __('activity_log.date_from') }}</label>
            <input type="date" name="from" value="{{ request('from') }}" class="dj-admin-input w-auto">
        </div>
        <div>
            <label class="dj-admin-label">{{ __('activity_log.date_to') }}</label>
            <input type="date" name="to" value="{{ request('to') }}" class="dj-admin-input w-auto">
        </div>
        <button class="dj-admin-btn dj-admin-btn-secondary">{{ __('activity_log.apply') }}</button>
    </form>

    <div class="dj-admin-card dj-admin-table-wrap">
        <table class="dj-admin-table">
            <thead>
                <tr>
                    <th>{{ __('activity_log.column_timestamp') }}</th>
                    <th>{{ __('activity_log.column_user') }}</th>
                    <th>{{ __('activity_log.column_action') }}</th>
                    <th>{{ __('activity_log.column_subject') }}</th>
                    <th>{{ __('activity_log.column_description') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    @php $djSubjectShortName = class_basename($log->subject_type); @endphp
                    <tr>
                        <td class="whitespace-nowrap">{{ $log->created_at->setTimezone('Africa/Cairo')->format('Y-m-d H:i') }}</td>
                        <td>{{ $log->user->name ?? __('activity_log.system') }}</td>
                        <td>
                            <span class="dj-admin-badge dj-admin-badge-info">
                                {{ $djLabel('activity_log.actions.'.$log->action, $log->action) }}
                            </span>
                        </td>
                        <td>{{ $djLabel('activity_log.subjects.'.$djSubjectShortName, $djSubjectShortName) }}</td>
                        <td class="text-[var(--dj-ink)]">{{ $log->description }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="dj-admin-table-empty">{{ __('activity_log.no_logs') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>
@endsection
