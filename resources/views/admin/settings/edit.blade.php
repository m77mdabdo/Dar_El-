@extends('admin.layout')

@section('title', __('settings.title'))

@section('content')
    <div class="dj-admin-card p-4 sm:p-6 max-w-xl">
        <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <label class="dj-admin-label">{{ __('settings.store_name') }}</label>
                <input type="text" name="store_name" value="{{ old('store_name', $settings['store_name'] ?? '') }}" class="dj-admin-input">
            </div>
            <div>
                <label class="dj-admin-label">{{ __('settings.support_email') }}</label>
                <input type="email" name="support_email" value="{{ old('support_email', $settings['support_email'] ?? '') }}" class="dj-admin-input">
            </div>
            <div>
                <label class="dj-admin-label">{{ __('settings.whatsapp_number') }}</label>
                <input type="text" name="whatsapp_number" value="{{ old('whatsapp_number', $settings['whatsapp_number'] ?? '') }}" class="dj-admin-input">
            </div>
            <div>
                <label class="dj-admin-label">{{ __('settings.default_shipping_fee') }}</label>
                <input type="number" name="default_shipping_fee" value="{{ old('default_shipping_fee', $settings['default_shipping_fee'] ?? '') }}" class="dj-admin-input">
            </div>
            <div>
                <label class="dj-admin-label">{{ __('settings.facebook_url') }}</label>
                <input type="url" name="facebook_url" value="{{ old('facebook_url', $settings['facebook_url'] ?? '') }}" class="dj-admin-input">
            </div>
            <div>
                <label class="dj-admin-label">{{ __('settings.instagram_url') }}</label>
                <input type="url" name="instagram_url" value="{{ old('instagram_url', $settings['instagram_url'] ?? '') }}" class="dj-admin-input">
            </div>

            <div class="border-t border-[var(--dj-cream-2)] pt-4">
                <h2 class="font-semibold mb-3 text-[var(--dj-maroon-dark)]">{{ __('settings.site_images') }}</h2>

                @php
                    $imageFields = [
                        'home_hero_image',
                        'shop_hero_image',
                        'about_hero_image',
                        'about_story_image',
                        'services_hero_image',
                        'blog_hero_image',
                        'contact_hero_image',
                        'checkout_hero_image',
                    ];
                @endphp

                <div class="space-y-4">
                    @foreach ($imageFields as $key)
                        <div>
                            <label class="dj-admin-label">{{ __('settings.image_'.$key) }}</label>
                            @if ($settings[$key] ?? null)
                                <img src="{{ asset('storage/'.$settings[$key]) }}" class="w-full max-w-xs h-32 object-cover rounded-lg border border-[var(--dj-cream-2)] mb-2">
                            @endif
                            <input type="file" name="{{ $key }}" accept="image/*" class="w-full text-sm">
                            @error($key) <p class="dj-admin-error">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                    <p class="dj-admin-hint">{{ __('settings.images_hint') }}</p>
                </div>
            </div>

            <button type="submit" class="dj-admin-btn dj-admin-btn-primary">{{ __('settings.save_settings') }}</button>
        </form>
    </div>
@endsection
