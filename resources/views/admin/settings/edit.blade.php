@extends('admin.layout')

@section('title', 'Settings')

@section('content')
    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-4 max-w-xl">
        @csrf
        @method('PATCH')

        <div>
            <label class="block text-sm font-medium mb-1">Store Name</label>
            <input type="text" name="store_name" value="{{ old('store_name', $settings['store_name'] ?? '') }}" class="w-full rounded border-stone-300">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Support Email</label>
            <input type="email" name="support_email" value="{{ old('support_email', $settings['support_email'] ?? '') }}" class="w-full rounded border-stone-300">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">WhatsApp Number</label>
            <input type="text" name="whatsapp_number" value="{{ old('whatsapp_number', $settings['whatsapp_number'] ?? '') }}" class="w-full rounded border-stone-300">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Default Shipping Fee (EGP)</label>
            <input type="number" name="default_shipping_fee" value="{{ old('default_shipping_fee', $settings['default_shipping_fee'] ?? '') }}" class="w-full rounded border-stone-300">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Facebook URL</label>
            <input type="url" name="facebook_url" value="{{ old('facebook_url', $settings['facebook_url'] ?? '') }}" class="w-full rounded border-stone-300">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Instagram URL</label>
            <input type="url" name="instagram_url" value="{{ old('instagram_url', $settings['instagram_url'] ?? '') }}" class="w-full rounded border-stone-300">
        </div>

        <div class="border-t border-stone-200 pt-4">
            <h2 class="font-medium mb-3">Site Images</h2>

            @php
                $imageFields = [
                    'home_hero_image' => 'Homepage Hero Background',
                    'shop_hero_image' => 'Shop Page Hero',
                    'about_hero_image' => 'About Page Hero',
                    'about_story_image' => 'About Page Story Photo',
                    'services_hero_image' => 'Services Page Hero',
                    'blog_hero_image' => 'Blog Page Hero',
                    'contact_hero_image' => 'Contact Page Hero',
                    'checkout_hero_image' => 'Checkout Page Hero',
                ];
            @endphp

            <div class="space-y-4">
                @foreach ($imageFields as $key => $label)
                    <div>
                        <label class="block text-sm font-medium mb-2">{{ $label }}</label>
                        @if ($settings[$key] ?? null)
                            <img src="{{ asset('storage/'.$settings[$key]) }}" class="w-full max-w-xs h-32 object-cover rounded border border-stone-200 mb-2">
                        @endif
                        <input type="file" name="{{ $key }}" accept="image/*" class="w-full text-sm">
                        @error($key) <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                @endforeach
                <p class="text-xs text-stone-500">JPG, PNG, or WEBP. Max 4MB each. Uploading a new image replaces the current one.</p>
            </div>
        </div>

        <button type="submit" class="bg-rose-700 hover:bg-rose-800 text-white px-8 py-3 rounded">Save Settings</button>
    </form>
@endsection
