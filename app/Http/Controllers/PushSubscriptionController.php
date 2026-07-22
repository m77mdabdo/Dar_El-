<?php

namespace App\Http\Controllers;

use App\Models\BackInStockSubscription;
use App\Models\PushSubscription;
use App\Rules\ValidWebPushEndpoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class PushSubscriptionController extends Controller
{
    /**
     * Stores (or updates, keyed by endpoint) a browser's Web Push
     * subscription. Validates manually rather than $request->validate() for
     * the same reason as BackInStockSubscriptionController::store() — this
     * isn't an api/* route, so a ValidationException wouldn't auto-render as
     * JSON, and the frontend needs JSON either way.
     *
     * link_token is optional: present only when this subscribe call follows
     * a back-in-stock signup the customer just chose to also get a push for
     * (see partials/back-in-stock-notify.blade.php). It's a short-lived,
     * single-use, unguessable reference minted by
     * BackInStockSubscriptionController::store() — not the subscription's
     * own numeric id — specifically so this public endpoint can't be used to
     * attach an arbitrary push subscription to someone else's back-in-stock
     * signup by guessing/enumerating ids.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // 'url' alone used to be the only check here — it accepts any
            // scheme/host at all, including internal/loopback addresses.
            // PushNotificationService later makes a real server-side HTTP
            // request to exactly this value (see minishlink/web-push's
            // sendOneNotification()), so an unrestricted endpoint is a
            // genuine SSRF vector: register an internal URL, then trigger
            // any event that pushes to it (an order-status change on your
            // own order, or a back-in-stock fulfillment) and the server
            // makes the request for you. ValidWebPushEndpoint is an
            // allowlist of the real push services browsers use — see its
            // own docblock for why an allowlist, not a blocklist.
            'endpoint' => ['required', 'string', 'max:400', new ValidWebPushEndpoint],
            // Real key material: p256dh is a base64url-encoded, uncompressed
            // EC P-256 public key (always 65 raw bytes -> ~87 chars, no
            // padding, confirmed against a real browser-generated
            // subscription); auth is a 16-byte secret (~22 chars). The
            // regex enforces the base64url charset; the length bounds have
            // headroom for minor cross-browser padding differences without
            // accepting obviously-garbage values.
            'keys.p256dh' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]+=*$/', 'min:80', 'max:100'],
            'keys.auth' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]+=*$/', 'min:20', 'max:30'],
            'link_token' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $subscription = PushSubscription::updateOrCreate(
            ['endpoint' => $validated['endpoint']],
            [
                'user_id' => $request->user()?->id,
                'p256dh' => $validated['keys']['p256dh'],
                'auth' => $validated['keys']['auth'],
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]
        );

        if (! empty($validated['link_token'])) {
            $backInStockSubscriptionId = Cache::pull('push-link-'.$validated['link_token']);

            if ($backInStockSubscriptionId) {
                BackInStockSubscription::whereKey($backInStockSubscriptionId)
                    ->whereNull('push_subscription_id')
                    ->update(['push_subscription_id' => $subscription->id]);
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
