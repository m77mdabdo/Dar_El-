@extends('admin.layout')

@section('title', __('admin.profile.title'))

@section('content')
    <div class="max-w-2xl space-y-6">
        <div class="dj-admin-card p-4 sm:p-6">
            <h2 class="font-semibold mb-4 text-[var(--dj-maroon-dark)]">{{ __('admin.profile.details') }}</h2>
            <form method="POST" action="{{ route('admin.profile.update') }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label class="dj-admin-label">{{ __('admin.profile.name') }}</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" class="dj-admin-input" required>
                    @error('name') <p class="dj-admin-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="dj-admin-label">{{ __('admin.profile.email') }}</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" class="dj-admin-input" required>
                    @error('email') <p class="dj-admin-error">{{ $message }}</p> @enderror
                </div>

                <button class="dj-admin-btn dj-admin-btn-primary">{{ __('admin.profile.save_changes') }}</button>
            </form>
        </div>

        <div class="dj-admin-card p-4 sm:p-6">
            <h2 class="font-semibold mb-4 text-[var(--dj-maroon-dark)]">{{ __('admin.profile.update_password') }}</h2>
            <form method="POST" action="{{ route('admin.profile.password') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="dj-admin-label">{{ __('admin.profile.current_password') }}</label>
                    <input type="password" name="current_password" class="dj-admin-input" required>
                    @error('current_password') <p class="dj-admin-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="dj-admin-label">{{ __('admin.profile.new_password') }}</label>
                    <input type="password" name="password" class="dj-admin-input" required>
                    @error('password') <p class="dj-admin-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="dj-admin-label">{{ __('admin.profile.confirm_password') }}</label>
                    <input type="password" name="password_confirmation" class="dj-admin-input" required>
                </div>

                <button class="dj-admin-btn dj-admin-btn-primary">{{ __('admin.profile.save_changes') }}</button>
            </form>
        </div>
    </div>
@endsection
