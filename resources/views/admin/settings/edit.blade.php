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
                <input type="text" name="whatsapp_number" value="{{ old('whatsapp_number', $settings['whatsapp_number'] ?? '') }}" class="dj-admin-input" placeholder="201234567890">
                <p class="dj-admin-hint">{{ __('settings.whatsapp_number_hint') }}</p>
                <p class="dj-admin-hint">{{ __('settings.whatsapp_number_features_hint') }}</p>
            </div>
            <div>
                <label class="dj-admin-label">{{ __('settings.business_address') }}</label>
                <textarea name="business_address" rows="2" class="dj-admin-input">{{ old('business_address', $settings['business_address'] ?? '') }}</textarea>
                @error('business_address') <p class="dj-admin-error">{{ $message }}</p> @enderror
                <p class="dj-admin-hint">{{ __('settings.business_address_hint') }}</p>
            </div>
            <div>
                <label class="dj-admin-label">{{ __('settings.business_hours') }}</label>
                <input type="text" name="business_hours" value="{{ old('business_hours', $settings['business_hours'] ?? '') }}" class="dj-admin-input" placeholder="Mo-Sa 10:00-22:00">
                @error('business_hours') <p class="dj-admin-error">{{ $message }}</p> @enderror
                <p class="dj-admin-hint">{{ __('settings.business_hours_hint') }}</p>
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
            <div>
                <label class="dj-admin-label">{{ __('settings.tiktok_url') }}</label>
                <input type="url" name="tiktok_url" value="{{ old('tiktok_url', $settings['tiktok_url'] ?? '') }}" class="dj-admin-input">
            </div>

            <div class="border-t border-[var(--dj-cream-2)] pt-4">
                <h2 class="font-semibold mb-3 text-[var(--dj-maroon-dark)]">{{ __('settings.security') }}</h2>
                <label class="flex items-center gap-2 text-sm text-[var(--dj-ink)]">
                    <input type="checkbox" name="login_alerts_enabled" value="1" {{ old('login_alerts_enabled', $settings['login_alerts_enabled'] ?? '1') === '1' ? 'checked' : '' }}>
                    {{ __('settings.login_alerts_enabled') }}
                </label>
                <p class="dj-admin-hint">{{ __('settings.login_alerts_enabled_hint') }}</p>
            </div>

            <div class="border-t border-[var(--dj-cream-2)] pt-4">
                <h2 class="font-semibold mb-3 text-[var(--dj-maroon-dark)]">{{ __('settings.cart_reminders') }}</h2>

                <label class="flex items-center gap-2 text-sm text-[var(--dj-ink)] mb-3">
                    <input type="checkbox" name="cart_reminders_enabled" value="1" {{ old('cart_reminders_enabled', $settings['cart_reminders_enabled'] ?? '1') === '1' ? 'checked' : '' }}>
                    {{ __('settings.cart_reminders_enabled') }}
                </label>

                <label class="flex items-center gap-2 text-sm text-[var(--dj-ink)] mb-3">
                    <input type="checkbox" name="cart_reminder_notification_enabled" value="1" {{ old('cart_reminder_notification_enabled', $settings['cart_reminder_notification_enabled'] ?? '1') === '1' ? 'checked' : '' }}>
                    {{ __('settings.cart_reminder_notification_enabled') }}
                </label>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="dj-admin-label">{{ __('settings.cart_reminder_first_delay_hours') }}</label>
                        <input type="number" min="1" max="72" name="cart_reminder_first_delay_hours" value="{{ old('cart_reminder_first_delay_hours', $settings['cart_reminder_first_delay_hours'] ?? 1) }}" class="dj-admin-input">
                        @error('cart_reminder_first_delay_hours') <p class="dj-admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="dj-admin-label">{{ __('settings.cart_reminder_interval_hours') }}</label>
                        <input type="number" min="1" max="168" name="cart_reminder_interval_hours" value="{{ old('cart_reminder_interval_hours', $settings['cart_reminder_interval_hours'] ?? 4) }}" class="dj-admin-input">
                        @error('cart_reminder_interval_hours') <p class="dj-admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="dj-admin-label">{{ __('settings.cart_max_reminders') }}</label>
                        <input type="number" min="0" max="10" name="cart_max_reminders" value="{{ old('cart_max_reminders', $settings['cart_max_reminders'] ?? 3) }}" class="dj-admin-input">
                        @error('cart_max_reminders') <p class="dj-admin-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <p class="dj-admin-hint">{{ __('settings.cart_reminders_hint') }}</p>
            </div>

            <div class="border-t border-[var(--dj-cream-2)] pt-4">
                <h2 class="font-semibold mb-3 text-[var(--dj-maroon-dark)]">{{ __('settings.sitewide_offer') }}</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="dj-admin-label">{{ __('settings.sitewide_offer_label') }}</label>
                        <input type="text" name="sitewide_offer_label" value="{{ old('sitewide_offer_label', $settings['sitewide_offer_label'] ?? '') }}" class="dj-admin-input" placeholder="عرض نهاية الأسبوع">
                        @error('sitewide_offer_label') <p class="dj-admin-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="dj-admin-label">{{ __('settings.sitewide_offer_end_at') }}</label>
                        <input type="datetime-local" name="sitewide_offer_end_at" value="{{ old('sitewide_offer_end_at', $settings['sitewide_offer_end_at'] ?? '') }}" class="dj-admin-input">
                        @error('sitewide_offer_end_at') <p class="dj-admin-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <p class="dj-admin-hint">{{ __('settings.sitewide_offer_hint') }}</p>
            </div>

            <div class="border-t border-[var(--dj-cream-2)] pt-4">
                <h2 class="font-semibold mb-3 text-[var(--dj-maroon-dark)]">{{ __('settings.marketing_tracking') }}</h2>

                <div class="space-y-4">
                    <div>
                        <label class="dj-admin-label">{{ __('settings.meta_pixel_id') }}</label>
                        <input type="text" name="meta_pixel_id" value="{{ old('meta_pixel_id', $settings['meta_pixel_id'] ?? '') }}" class="dj-admin-input" placeholder="123456789012345">
                        <p class="dj-admin-hint">{{ __('settings.tracking_id_hint') }}</p>
                    </div>
                    <div>
                        <label class="dj-admin-label">{{ __('settings.tiktok_pixel_id') }}</label>
                        <input type="text" name="tiktok_pixel_id" value="{{ old('tiktok_pixel_id', $settings['tiktok_pixel_id'] ?? '') }}" class="dj-admin-input" placeholder="C4A1B2C3D4E5F6G7H8I9">
                        <p class="dj-admin-hint">{{ __('settings.tracking_id_hint') }}</p>
                    </div>
                    <div>
                        <label class="dj-admin-label">{{ __('settings.ga4_measurement_id') }}</label>
                        <input type="text" name="ga4_measurement_id" value="{{ old('ga4_measurement_id', $settings['ga4_measurement_id'] ?? '') }}" class="dj-admin-input" placeholder="G-XXXXXXXXXX">
                        <p class="dj-admin-hint">{{ __('settings.tracking_id_hint') }}</p>
                    </div>
                </div>
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
