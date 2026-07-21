<?php

namespace App\Http\Controllers;

use App\Models\BackInStockSubscription;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BackInStockSubscriptionController extends Controller
{
    /**
     * AJAX signup from the product page. product_size_id is null for a
     * whole-product subscription (product has no meaningful size choice),
     * or a specific ProductSize id for a per-size subscription.
     *
     * Validates manually (rather than $request->validate()) because this
     * app's exception handler only auto-renders ValidationException as JSON
     * for api/* routes (see bootstrap/app.php's shouldRenderJsonWhen) — this
     * route isn't one, and the frontend widget needs JSON either way. Same
     * reasoning as Admin\ProductController::autosave().
     */
    public function store(Request $request, Product $product): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'max:255'],
            'product_size_id' => ['nullable', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $productSizeId = null;

        if (! empty($validated['product_size_id'])) {
            $size = $product->sizes()->whereKey($validated['product_size_id'])->first();
            abort_unless($size, 404);
            $productSizeId = $size->id;
        }

        $existing = BackInStockSubscription::where('product_id', $product->id)
            ->where('product_size_id', $productSizeId)
            ->where('email', $validated['email'])
            ->first();

        // A row that was already notified (notified_at set) represents a
        // *past* restock — BackInStockService only ever queries
        // whereNull('notified_at'), so leaving it as-is would silently
        // exclude this subscriber from every future restock while this
        // response tells them they're covered. Resetting it here re-arms
        // the same row for the next 0-to-positive crossing instead of
        // leaving them permanently locked out after their first notification.
        $alreadyWaiting = $existing && $existing->notified_at === null;

        if (! $existing) {
            $subscription = BackInStockSubscription::create([
                'product_id' => $product->id,
                'product_size_id' => $productSizeId,
                'email' => $validated['email'],
                'user_id' => $request->user()?->id,
            ]);
        } else {
            if ($existing->notified_at !== null) {
                $existing->update(['notified_at' => null]);
            }
            $subscription = $existing;
        }

        // Short-lived, single-use, unguessable reference the frontend can
        // hand back to POST /push/subscribe if this customer also opts into
        // a push notification for this signup right after seeing this
        // response (see partials/back-in-stock-notify.blade.php). Deliberately
        // not the subscription's own numeric id — that would let anyone
        // attach their own push subscription to someone else's back-in-stock
        // row just by enumerating ids. 15 minutes is more than enough time
        // for the "want a push too?" prompt to still be on screen.
        $pushLinkToken = (string) Str::uuid();
        Cache::put('push-link-'.$pushLinkToken, $subscription->id, now()->addMinutes(15));

        // Both the fresh-signup and already-waiting cases return the same
        // friendly, successful-looking response — a customer who signed up
        // last week and forgot shouldn't see anything that reads as an error.
        // A previously-notified subscriber gets the "we'll notify you"
        // message too, since they've just been re-armed for the next restock.
        return response()->json([
            'status' => 'ok',
            'message' => $alreadyWaiting
                ? __("You're already on the list for this — we'll email you the moment it's back.")
                : __("We'll notify you as soon as it's back in stock."),
            'push_link_token' => $pushLinkToken,
        ]);
    }

    /**
     * Signed, guest-safe unsubscribe link used in the notification email —
     * no login required, security comes entirely from the URL signature
     * (see the 'signed' middleware on this route), same convention as
     * InvoiceDownloadController.
     */
    public function unsubscribe(BackInStockSubscription $subscription): RedirectResponse
    {
        $subscription->delete();

        return redirect()->route('home')->with('status', __("You won't receive any more notifications for that product."));
    }
}
