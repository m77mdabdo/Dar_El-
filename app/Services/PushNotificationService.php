<?php

namespace App\Services;

use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

/**
 * Thin wrapper around minishlink/web-push. Every public method is
 * self-contained and non-throwing — same contract as StockAlertService and
 * BackInStockService, so callers (BackInStockService, the order-status
 * controller) never need their own try/catch around a push send.
 */
class PushNotificationService
{
    /**
     * Null when VAPID keys aren't configured — every method below becomes a
     * safe no-op rather than throwing, so push notifications are purely
     * optional infrastructure: leaving the env vars unset simply disables
     * push everywhere, without touching the mail/database channels it sits
     * alongside.
     */
    protected ?WebPush $webPush;

    public function __construct()
    {
        $publicKey = config('services.webpush.public_key');
        $privateKey = config('services.webpush.private_key');

        $this->webPush = ($publicKey && $privateKey)
            ? new WebPush([
                'VAPID' => [
                    'subject' => config('services.webpush.subject'),
                    'publicKey' => $publicKey,
                    'privateKey' => $privateKey,
                ],
            ])
            : null;
    }

    public function isConfigured(): bool
    {
        return $this->webPush !== null;
    }

    /**
     * Sends to every device the given user has subscribed on (a user can
     * have more than one PushSubscription row — one per browser/device).
     */
    public function sendToUser(int $userId, string $title, string $body, ?string $url = null): void
    {
        if (! $this->webPush) {
            return;
        }

        PushSubscription::where('user_id', $userId)->get()
            ->each(fn (PushSubscription $subscription) => $this->sendToSubscription($subscription, $title, $body, $url));
    }

    public function sendToSubscription(PushSubscription $subscription, string $title, string $body, ?string $url = null): void
    {
        if (! $this->webPush) {
            return;
        }

        try {
            $webPushSubscription = Subscription::create([
                'endpoint' => $subscription->endpoint,
                'publicKey' => $subscription->p256dh,
                'authToken' => $subscription->auth,
            ]);

            $payload = json_encode([
                'title' => $title,
                'body' => $body,
                'url' => $url ?? config('app.url'),
            ]);

            $report = $this->webPush->sendOneNotification($webPushSubscription, $payload);

            if (! $report->isSuccess()) {
                if ($report->isSubscriptionExpired()) {
                    // The push service itself says this endpoint is gone for
                    // good (browser unsubscribed, site data cleared, device
                    // reset, etc.) — delete it so we stop trying forever,
                    // rather than logging the same dead endpoint on every
                    // future back-in-stock or order-status event.
                    $subscription->delete();
                } else {
                    Log::warning('Push notification delivery failed', [
                        'subscription_id' => $subscription->id,
                        'reason' => $report->getReason(),
                    ]);
                }
            }
        } catch (Throwable $e) {
            Log::error('Push notification send threw an exception', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
