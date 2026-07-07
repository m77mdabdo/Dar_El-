@extends('admin.layout')

@section('title', __('admin.profile.title'))

@section('content')
    <div class="max-w-2xl space-y-6">
        <div class="bg-white border border-stone-200 rounded-lg p-4 sm:p-6">
            <h2 class="font-medium mb-4">{{ __('admin.profile.details') }}</h2>
            <form method="POST" action="{{ route('admin.profile.update') }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label class="block text-sm text-stone-600 mb-1">{{ __('admin.profile.name') }}</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" class="w-full rounded border-stone-300 text-sm" required>
                    @error('name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm text-stone-600 mb-1">{{ __('admin.profile.email') }}</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" class="w-full rounded border-stone-300 text-sm" required>
                    @error('email') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <button class="bg-rose-700 hover:bg-rose-800 text-white text-sm px-4 py-2 rounded">{{ __('admin.profile.save_changes') }}</button>
            </form>
        </div>

        <div class="bg-white border border-stone-200 rounded-lg p-4 sm:p-6">
            <h2 class="font-medium mb-4">{{ __('admin.profile.update_password') }}</h2>
            <form method="POST" action="{{ route('admin.profile.password') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-sm text-stone-600 mb-1">{{ __('admin.profile.current_password') }}</label>
                    <input type="password" name="current_password" class="w-full rounded border-stone-300 text-sm" required>
                    @error('current_password') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm text-stone-600 mb-1">{{ __('admin.profile.new_password') }}</label>
                    <input type="password" name="password" class="w-full rounded border-stone-300 text-sm" required>
                    @error('password') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm text-stone-600 mb-1">{{ __('admin.profile.confirm_password') }}</label>
                    <input type="password" name="password_confirmation" class="w-full rounded border-stone-300 text-sm" required>
                </div>

                <button class="bg-rose-700 hover:bg-rose-800 text-white text-sm px-4 py-2 rounded">{{ __('admin.profile.save_changes') }}</button>
            </form>
        </div>
    </div>
@endsection
