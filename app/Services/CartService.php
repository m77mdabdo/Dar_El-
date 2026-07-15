<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\Setting;
use Illuminate\Support\Facades\Session;

class CartService
{
    protected const SESSION_KEY = 'cart';

    /**
     * Cart contents: [productId.'-'.size => ['product_id', 'size', 'quantity']]
     */
    public function content(): array
    {
        return Session::get(self::SESSION_KEY, []);
    }

    public function add(Product $product, string $size, int $quantity = 1): void
    {
        $productSize = $product->sizes()->where('size', $size)->first();

        if (! $productSize) {
            throw new \RuntimeException(__('This size is not available.'));
        }

        $cart = $this->content();
        $key = $product->id.'-'.$size;

        $existingQty = $cart[$key]['quantity'] ?? 0;
        $newQty = $existingQty + $quantity;

        if ($newQty > $productSize->stock) {
            throw new \RuntimeException($this->stockLimitMessage($productSize->stock));
        }

        $cart[$key] = [
            'product_id' => $product->id,
            'size' => $size,
            'quantity' => $newQty,
        ];

        Session::put(self::SESSION_KEY, $cart);
    }

    public function update(string $key, int $quantity): void
    {
        $cart = $this->content();

        if (! isset($cart[$key])) {
            return;
        }

        if ($quantity <= 0) {
            unset($cart[$key]);
            Session::put(self::SESSION_KEY, $cart);

            return;
        }

        $productSize = ProductSize::where('product_id', $cart[$key]['product_id'])
            ->where('size', $cart[$key]['size'])
            ->first();

        if ($productSize && $quantity > $productSize->stock) {
            throw new \RuntimeException($this->stockLimitMessage($productSize->stock));
        }

        $cart[$key]['quantity'] = $quantity;

        Session::put(self::SESSION_KEY, $cart);
    }

    protected function stockLimitMessage(int $available): string
    {
        return $available > 0
            ? __('You can only order :count piece(s) of this size.', ['count' => $available])
            : __('This size just sold out.');
    }

    public function remove(string $key): void
    {
        $cart = $this->content();
        unset($cart[$key]);
        Session::put(self::SESSION_KEY, $cart);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
        Session::forget('coupon_code');
    }

    /**
     * Cart items with hydrated product/size data.
     */
    public function items(): array
    {
        $items = [];

        foreach ($this->content() as $key => $row) {
            $product = Product::with(['images', 'sizes'])->find($row['product_id']);

            if (! $product) {
                continue;
            }

            $stock = $product->stockForSize($row['size']);

            $items[] = [
                'key' => $key,
                'product' => $product,
                'size' => $row['size'],
                'quantity' => $row['quantity'],
                'subtotal' => $product->price * $row['quantity'],
                'stock' => $stock,
                'exceeds_stock' => $row['quantity'] > $stock,
            ];
        }

        return $items;
    }

    /**
     * Whether every item in the cart is still within available stock.
     * Used to gate checkout on the cart/checkout pages before the
     * transaction-safe re-check happens at order creation time.
     */
    public function isValid(): bool
    {
        return collect($this->items())->every(fn ($item) => ! $item['exceeds_stock']);
    }

    public function subtotal(): int
    {
        return collect($this->items())->sum('subtotal');
    }

    public function count(): int
    {
        return collect($this->content())->sum('quantity');
    }

    public function appliedCoupon(): ?Coupon
    {
        $code = Session::get('coupon_code');

        if (! $code) {
            return null;
        }

        $coupon = Coupon::where('code', $code)->first();

        return $coupon && $coupon->isValidFor($this->subtotal()) ? $coupon : null;
    }

    public function applyCoupon(string $code): Coupon
    {
        $coupon = Coupon::where('code', $code)->firstOrFail();

        if (! $coupon->isValidFor($this->subtotal())) {
            throw new \RuntimeException(__('This coupon is not valid for your order.'));
        }

        Session::put('coupon_code', $coupon->code);

        return $coupon;
    }

    public function removeCoupon(): void
    {
        Session::forget('coupon_code');
    }

    public function discount(): int
    {
        $coupon = $this->appliedCoupon();

        return $coupon ? $coupon->discountFor($this->subtotal()) : 0;
    }

    /**
     * The shipping fee shown pre-checkout, before a ShippingMethod has
     * been selected — the same 'default_shipping_fee' Setting
     * CheckoutController itself falls back to when nothing is selected.
     * An estimate, not a guarantee: the customer's eventual choice at
     * checkout can carry a different fee.
     */
    public function estimatedShippingFee(): int
    {
        return (int) Setting::get('default_shipping_fee', 0);
    }

    /**
     * The single source of truth for "what would this cart actually cost,
     * including shipping" — matches exactly what CheckoutController
     * charges: subtotal + shipping - discount, floored at 0.
     *
     * $shippingFee is optional because most callers of this method (cart
     * page, cart-drawer widget, abandoned-cart reminder emails) run
     * BEFORE checkout and have no ShippingMethod selected yet, so they
     * fall back to estimatedShippingFee(). CheckoutController passes the
     * actual selected fee explicitly once one exists, so its own total is
     * always exact.
     */
    public function totalIncludingShipping(?int $shippingFee = null): int
    {
        $shippingFee ??= $this->estimatedShippingFee();

        return max(0, $this->subtotal() + $shippingFee - $this->discount());
    }
}
