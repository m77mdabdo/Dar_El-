<section>
    <header>
        <h2 class="text-lg font-medium text-stone-900">
            {{ __('Profile Photo') }}
        </h2>

        @if ($user->isSocialAccount())
            <p class="mt-1 text-sm text-stone-600">
                {{ __('Connected with :provider', ['provider' => $user->registrationMethodLabel()]) }}
            </p>
        @endif
    </header>

    <div class="mt-4 flex items-center gap-5">
        @if ($user->avatar_url)
            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-16 h-16 rounded-full object-cover border border-stone-200">
        @else
            <span class="w-16 h-16 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center text-xl font-semibold">
                {{ Str::of($user->name)->substr(0, 1)->upper() }}
            </span>
        @endif

        <form method="post" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data" class="flex items-center gap-3">
            @csrf
            @method('patch')

            <label class="cursor-pointer text-sm text-rose-700 underline">
                {{ __('Upload a new photo') }}
                <input type="file" name="avatar" accept="image/*" class="hidden" onchange="this.form.submit()">
            </label>
        </form>
    </div>

    @if ($user->isSocialAccount() && ! $user->avatar_path)
        <p class="mt-2 text-xs text-stone-500">
            {{ __('Your :provider avatar is shown until you upload your own.', ['provider' => $user->registrationMethodLabel()]) }}
        </p>
    @endif

    <x-input-error class="mt-2" :messages="$errors->get('avatar')" />

    @if (session('status') === 'avatar-updated')
        <p class="mt-2 text-sm text-green-700">{{ __('Saved.') }}</p>
    @endif
</section>
