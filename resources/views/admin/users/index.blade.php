@extends('admin.layout')

@section('title', __('users.title'))

@section('content')
    <div class="flex flex-col sm:flex-row sm:justify-between gap-3 mb-4">
        <form method="GET" class="flex flex-wrap gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('users.search_placeholder') }}" class="dj-admin-input w-full sm:w-auto">
            <select name="role" onchange="this.form.submit()" class="dj-admin-input w-auto">
                <option value="">{{ __('users.all_roles') }}</option>
                <option value="super_admin" {{ request('role') === 'super_admin' ? 'selected' : '' }}>{{ __('users.role_super_admin') }}</option>
                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>{{ __('users.role_admin') }}</option>
                <option value="employee" {{ request('role') === 'employee' ? 'selected' : '' }}>{{ __('users.role_employee') }}</option>
            </select>
            <button class="dj-admin-btn dj-admin-btn-secondary shrink-0">{{ __('general.search') }}</button>
        </form>
        <a href="{{ route('admin.users.create') }}" class="dj-admin-btn dj-admin-btn-primary text-center">+ {{ __('users.add_user') }}</a>
    </div>

    <div class="dj-admin-card dj-admin-table-wrap">
        <table class="dj-admin-table">
            <thead>
                <tr>
                    <th>{{ __('users.name') }}</th>
                    <th>{{ __('users.email') }}</th>
                    <th>{{ __('users.role') }}</th>
                    <th>{{ __('users.status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td class="font-medium text-[var(--dj-ink)]">{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <span class="dj-admin-badge dj-admin-badge-neutral">{{ __('users.role_'.$user->roles->first()?->name) }}</span>
                            @if ($user->isPrimarySuperAdmin())
                                <span class="dj-admin-badge dj-admin-badge-success" title="{{ __('users.primary_super_admin_hint') }}">{{ __('users.primary_super_admin_badge') }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="dj-admin-badge {{ $user->isDisabled() ? 'dj-admin-badge-neutral' : 'dj-admin-badge-success' }}">
                                {{ $user->isDisabled() ? __('users.inactive') : __('users.active') }}
                            </span>
                        </td>
                        <td class="text-end space-x-3 rtl:space-x-reverse whitespace-nowrap">
                            <a href="{{ route('admin.users.edit', $user) }}" class="dj-admin-link">{{ __('general.edit') }}</a>

                            @if ($user->id !== auth()->id() && ! $user->isPrimarySuperAdmin())
                                <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}" class="inline" onsubmit="return confirm('{{ __('users.confirm_toggle_active') }}')">
                                    @csrf
                                    @method('PATCH')
                                    <button class="dj-admin-link">{{ $user->isDisabled() ? __('users.active') : __('users.inactive') }}</button>
                                </form>
                            @endif

                            @if ($user->id !== auth()->id())
                                <form method="POST" action="{{ route('admin.users.force-logout', $user) }}" class="inline" onsubmit="return confirm('{{ __('users.confirm_force_logout') }}')">
                                    @csrf
                                    <button class="dj-admin-link">{{ __('users.force_logout') }}</button>
                                </form>
                            @endif

                            <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" class="inline" onsubmit="return confirm('{{ __('users.confirm_reset_password') }}')">
                                @csrf
                                <button class="dj-admin-link">{{ __('users.reset_password') }}</button>
                            </form>

                            @if ($user->id !== auth()->id() && ! $user->isPrimarySuperAdmin())
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline" onsubmit="return confirm('{{ __('users.confirm_delete') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="dj-admin-link-muted">{{ __('general.delete') }}</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="dj-admin-table-empty">{{ __('users.no_users') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection
