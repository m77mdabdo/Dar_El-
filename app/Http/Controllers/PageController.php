<?php

namespace App\Http\Controllers;

use App\Models\Setting;

class PageController extends Controller
{
    public function about()
    {
        $heroImage = Setting::get('about_hero_image', 'https://images.unsplash.com/photo-1772474587292-08b3e8932acd?w=1600&q=80&auto=format&fit=crop');
        $storyImage = Setting::get('about_story_image', 'https://images.unsplash.com/photo-1772474557170-4818d01d7bca?w=900&q=80&auto=format&fit=crop');

        return view('pages.about', compact('heroImage', 'storyImage'));
    }

    public function services()
    {
        $heroImage = Setting::get('services_hero_image', 'https://images.unsplash.com/photo-1772474528936-4f1187eb1611?w=1600&q=80&auto=format&fit=crop');

        return view('pages.services', compact('heroImage'));
    }

    public function returnPolicy()
    {
        $heroImage = Setting::get('return_policy_hero_image', 'https://images.unsplash.com/photo-1591369822096-ffd140ec948f?w=1600&q=80&auto=format&fit=crop');

        return view('pages.return-policy', compact('heroImage'));
    }
}
