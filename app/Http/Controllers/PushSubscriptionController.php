<?php

namespace App\Http\Controllers;

use App\Models\BackInStockSubscription;
use App\Models\PushSubscription;
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
            'endpoint' => ['required', 'url', 'max:400'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
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
