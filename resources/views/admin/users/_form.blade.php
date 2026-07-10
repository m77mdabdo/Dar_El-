@php
    $djSelectedRole = old('role', isset($user) ? $user->roles->first()?->name : 'employee');
    $djIsPrimarySuperAdmin = isset($user) && $user->isPrimarySuperAdmin();
@endphp
@csrf
<div x-data="{ role: {{ json_encode($djSelectedRole) }} }" class="space-y-4">
    @if ($djIsPrimarySuperAdmin)
        <div class="dj-admin-alert dj-admin-alert-success">{{ __('users.primary_super_admin_hint') }}</div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="dj-admin-label">{{ __('users.name') }}</label>
            <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required class="dj-admin-input">
            @error('name') <p class="dj-admin-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="dj-admin-label">{{ __('users.email') }}</label>
            <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required class="dj-admin-input" {{ $djIsPrimarySuperAdmin ? 'readonly' : '' }}>
            @error('email') <p class="dj-admin-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="dj-admin-label">{{ __('users.phone') }}</label>
            <input type="text" name="phone" value="{{ old('phone', $user->phone ?? '') }}" class="dj-admin-input">
            @error('phone') <p class="dj-admin-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="dj-admin-label">{{ __('users.role') }}</label>
            @if ($djIsPrimarySuperAdmin)
                <input type="hidden" name="role" value="super_admin">
                <select disabled class="dj-admin-input opacity-60">
                    <option selected>{{ __('users.role_super_admin') }}</option>
                </select>
            @else
                <select name="role" x-model="role" required class="dj-admin-input">
                    <option value="super_admin" {{ $djSelectedRole === 'super_admin' ? 'selected' : '' }}>{{ __('users.role_super_admin') }}</option>
                    <option value="admin" {{ $djSelectedRole === 'admin' ? 'selected' : '' }}>{{ __('users.role_admin') }}</option>
                    <option value="employee" {{ $djSelectedRole === 'employee' ? 'selected' : '' }}>{{ __('users.role_employee') }}</option>
                </select>
            @endif
            @error('role') <p class="dj-admin-error">{{ $message }}</p> @enderror
        </div>
    </div>

    @unless (isset($user))
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="dj-admin-label">{{ __('users.password') }}</label>
                <input type="password" name="password" required class="dj-admin-input">
                @error('password') <p class="dj-admin-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="dj-admin-label">{{ __('users.confirm_password') }}</label>
                <input type="password" name="password_confirmation" required class="dj-admin-input">
            </div>
        </div>
    @endunless

    <div class="flex flex-wrap gap-6">
        <label class="dj-admin-checkbox-row">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', isset($user) ? ! $user->isDisabled() : true) ? 'checked' : '' }} {{ $djIsPrimarySuperAdmin ? 'disabled checked' : '' }}>
            {{ __('users.active') }}
        </label>

        @unless (isset($user))
            <label class="dj-admin-checkbox-row">
                <input type="checkbox" name="email_verified" value="1" {{ old('email_verified', true) ? 'checked' : '' }}>
                {{ __('users.email_verified') }}
            </label>
            <label class="dj-admin-checkbox-row">
                <input type="checkbox" name="send_welcome_email" value="1" {{ old('send_welcome_email', true) ? 'checked' : '' }}>
                {{ __('users.send_welcome_email') }}
            </label>
        @endunless
    </div>

    <div x-show="role === 'employee'" x-cloak>
        <h3 class="dj-admin-label mb-2">{{ __('users.permissions_title') }}</h3>
        <p class="dj-admin-hint mb-3">{{ __('users.permissions_hint') }}</p>
        @include('admin.users._permissions_grid')
    </div>

    <button type="submit" class="dj-admin-btn dj-admin-btn-primary">{{ __('users.save_user') }}</button>
</div>
