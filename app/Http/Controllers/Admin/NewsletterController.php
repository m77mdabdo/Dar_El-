<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NewsletterController extends Controller
{
    public function index()
    {
        $subscribers = NewsletterSubscriber::latest()->paginate(30);

        return view('admin.newsletter.index', compact('subscribers'));
    }

    public function export(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [__('general.email'), __('newsletter.subscribed_at')]);

            NewsletterSubscriber::orderBy('created_at')->chunk(200, function ($subscribers) use ($handle) {
                foreach ($subscribers as $subscriber) {
                    fputcsv($handle, [$subscriber->email, $subscriber->created_at->toDateTimeString()]);
                }
            });

            fclose($handle);
        }, 'newsletter-subscribers.csv');
    }
}
