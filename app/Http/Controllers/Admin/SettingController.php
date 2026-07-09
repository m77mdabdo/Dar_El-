<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class SettingController extends Controller
{
    protected const KEYS = [
        'store_name', 'support_email', 'whatsapp_number',
        'default_shipping_fee', 'facebook_url', 'instagram_url', 'tiktok_url',
        'cart_reminder_first_delay_hours', 'cart_reminder_interval_hours', 'cart_max_reminders',
    ];

    protected const IMAGE_KEYS = [
        'home_hero_image', 'about_hero_image', 'about_story_image', 'services_hero_image',
        'shop_hero_image', 'blog_hero_image', 'contact_hero_image', 'checkout_hero_image',
    ];

    protected const BOOLEAN_KEYS = [
        'login_alerts_enabled', 'cart_reminders_enabled', 'cart_reminder_notification_enabled',
    ];

    public function __construct(protected ImageUploadService $imageUploader)
    {
    }

    public function edit()
    {
        $settings = Setting::whereIn('key', [...self::KEYS, ...self::IMAGE_KEYS, ...self::BOOLEAN_KEYS])->pluck('value', 'key');

        return view('admin.settings.edit', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = [
            'store_name' => ['nullable', 'string', 'max:255'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'default_shipping_fee' => ['nullable', 'integer', 'min:0'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'tiktok_url' => ['nullable', 'url', 'max:255'],
            'cart_reminder_first_delay_hours' => ['nullable', 'integer', 'min:1', 'max:72'],
            'cart_reminder_interval_hours' => ['nullable', 'integer', 'min:1', 'max:168'],
            'cart_max_reminders' => ['nullable', 'integer', 'min:0', 'max:10'],
        ];
        $messages = [];

        foreach (self::IMAGE_KEYS as $key) {
            $rules[$key] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'];
            $messages["{$key}.image"] = __('Please upload a valid image file.');
            $messages["{$key}.mimes"] = __('The image must be a JPG, PNG, or WEBP file.');
            $messages["{$key}.max"] = __('The image may not be larger than 4MB.');
        }

        $validated = $request->validate($rules, $messages);

        foreach (self::KEYS as $key) {
            if (array_key_exists($key, $validated)) {
                Setting::set($key, $validated[$key]);
            }
        }

        foreach (self::IMAGE_KEYS as $key) {
            if ($request->hasFile($key)) {
                Setting::set($key, $this->imageUploader->replace(Setting::get($key), $request->file($key), 'settings'));
            }
        }

        foreach (self::BOOLEAN_KEYS as $key) {
            Setting::set($key, $request->boolean($key) ? '1' : '0');
        }

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'updated',
            'subject_type' => Setting::class,
            'subject_id' => 0,
            'description' => 'Updated store settings',
        ]);

        return back()->with('status', __('settings.updated'));
    }
}
