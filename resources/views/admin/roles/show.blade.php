@extends('admin.layout')

@section('title', __('users.role_'.$role->name))

@section('content')
    <a href="{{ route('admin.roles.index') }}" class="dj-admin-link mb-4 inline-block">← {{ __('roles.back_to_roles') }}</a>

    <div class="dj-admin-card p-4 sm:p-6 mb-4">
        <h2 class="font-semibold text-lg mb-1">{{ __('users.role_'.$role->name) }}</h2>
        <p class="text-sm text-[var(--dj-rose-dust)]">{{ __('roles.'.$role->name.'_description') }}</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($permissionGroups as $groupKey => $groupPermissions)
            <div class="dj-admin-card p-3">
                <h3 class="font-semibold text-sm text-[var(--dj-maroon-dark)] mb-2">{{ __('permissions.groups.'.$groupKey) }}</h3>
                <ul class="space-y-1.5 text-sm">
                    @foreach ($groupPermissions as $permission)
                        @php $djGranted = in_array($permission, $rolePermissionNames, true); @endphp
                        <li class="flex items-center gap-2 {{ $djGranted ? 'text-[var(--dj-ink)]' : 'text-[var(--dj-rose-dust)] opacity-60' }}">
                            <span aria-hidden="true">{{ $djGranted ? '✓' : '✕' }}</span>
                            {{ __('permissions.'.$permission) }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
@endsection
