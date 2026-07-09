<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use App\Notifications\CartConvertedAdminNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Mirrors the session-backed CartService into a persisted Cart/CartItem pair
 * for authenticated users only, so the admin dashboard can see "who has items
 * in their cart right now" and abandoned-cart reminders can be sent. This never
 * reads from or writes to the session itself — CartService remains the single
 * source of truth for the live cart; this is a read-after-write shadow copy.
 */
class CartTrackingService
{
    /**
     * Mirror the user's current session cart into their open Cart row,
     * creating one if needed. If the session cart is empty, the open Cart
     * row (if any) is deleted outright rather than kept around empty.
     */
    public function sync(User $user, CartService $cart): void
    {
        $items = $cart->items();

        if (empty($items)) {
            $user->carts()->open()->delete();

            return;
        }

        $dbCart = $user->carts()->open()->first() ?? $user->carts()->create([
            'status' => 'active',
            'last_activity_at' => now(),
        ]);

        $dbCart->items()->delete();

        foreach ($items as $item) {
            $product = $item['product'];

            $dbCart->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name_en,
                'variant_snapshot' => ['size' => $item['size']],
                'image_snapshot' => $product->cover_image_src,
                'quantity' => $item['quantity'],
                'price' => $product->price,
                'total' => $item['subtotal'],
            ]);
        }

        $subtotal = $cart->subtotal();

        $dbCart->update([
            'status' => 'active',
            'subtotal' => $subtotal,
            'total' => max(0, $subtotal - $cart->discount()),
            'items_count' => $cart->count(),
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Close out the user's open cart as converted into the given order —
     * called right before CartService::clear() in the checkout flow.
     */
    public function markConverted(User $user, Order $order): void
    {
        $dbCart = $user->carts()->open()->first();

        if (! $dbCart) {
            return;
        }

        $dbCart->update([
            'status' => 'converted',
            'converted_at' => now(),
            'order_id' => $order->id,
        ]);

        Notification::send(User::admins(), new CartConvertedAdminNotification($dbCart));
    }
}
