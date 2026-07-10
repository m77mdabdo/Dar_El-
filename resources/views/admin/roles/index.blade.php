@extends('admin.layout')

@section('title', __('roles.title'))

@section('content')
    <div class="dj-admin-card dj-admin-table-wrap">
        <table class="dj-admin-table">
            <thead>
                <tr>
                    <th>{{ __('roles.role') }}</th>
                    <th>{{ __('general.description') }}</th>
                    <th>{{ __('roles.users_count') }}</th>
                    <th>{{ __('roles.permissions_count') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($roles as $role)
                    <tr>
                        <td class="font-medium text-[var(--dj-ink)]">
                            <span class="dj-admin-badge dj-admin-badge-neutral">{{ __('users.role_'.$role->name) }}</span>
                        </td>
                        <td class="text-sm text-[var(--dj-rose-dust)] max-w-md">{{ __('roles.'.$role->name.'_description') }}</td>
                        <td>{{ $role->users_count }}</td>
                        <td>{{ $role->name === 'customer' ? '—' : $role->permissions()->count() }}</td>
                        <td class="text-end">
                            @unless ($role->name === 'customer')
                                <a href="{{ route('admin.roles.show', $role) }}" class="dj-admin-link">{{ __('roles.view_permissions') }}</a>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
