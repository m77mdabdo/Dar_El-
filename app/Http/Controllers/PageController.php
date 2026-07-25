<?php

namespace App\Http\Controllers;

use App\Models\Faq;
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

    /**
     * Same hardcoded-fallback-hero pattern as returnPolicy() above (no
     * admin-editable Setting key for this one either). The question/
     * answer list itself falls back to Faq::fallbackList() — the exact
     * 5 questions that used to be hardcoded on the homepage — whenever
     * no admin has added a real FAQ yet, so this page is never blank
     * out of the box.
     */
    public function faq()
    {
        $heroImage = Setting::get('faq_hero_image', 'https://images.unsplash.com/photo-1591369822096-ffd140ec948f?w=1600&q=80&auto=format&fit=crop');

        $faqs = Faq::active()->orderBy('sort_order')->orderBy('id')->get();
        $faqs = $faqs->isNotEmpty()
            ? $faqs->map(fn (Faq $faq) => ['q' => trans_field($faq, 'question'), 'a' => trans_field($faq, 'answer')])->all()
            : Faq::fallbackList();

        return view('pages.faq', compact('heroImage', 'faqs'));
    }
}
