<?php

namespace App\Http\Requests;

use App\Models\Order;
use App\Models\ShippingMethod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCheckoutRequest extends FormRequest
{
    /**
     * Max orders the same phone number may place within the rate-limit
     * window below — see withValidator().
     */
    protected const MAX_ORDERS_PER_PHONE = 5;

    protected const RATE_LIMIT_WINDOW_MINUTES = 60;

    /**
     * Only fields actually rendered on the checkout page — no field is
     * validated here that the customer can't see and fill in.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            // Nullable: this is a COD-only store with no payment step, so
            // email isn't essential to fulfillment — guests can skip it.
            // Order::resolveCustomerEmail() and everything downstream of it
            // already treats a missing email as "no confirmation email to
            // send" rather than an error (see CheckoutController::store()).
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'governorate' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
            // Accepts either a real, currently-active shipping_methods.id,
            // or the literal string "standard" — the checkout page always
            // renders at least one real active method (self-healed by
            // ShippingMethod::ensureAtLeastOneActive(), called before this
            // validates), so "standard" is a last-resort fallback that
            // should in practice never actually be needed, not the normal
            // path. Never a hard "shipping method required" dead end.
            'shipping_method_id' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === 'standard') {
                        return;
                    }

                    $exists = ShippingMethod::whereKey($value)->where('is_active', true)->exists();

                    if (! $exists) {
                        $fail(__('Please select a valid shipping method.'));
                    }
                },
            ],
            'payment_method' => ['required', Rule::in([Order::PAYMENT_METHOD_COD])],
            // Optional "Use My Current Location" capture — never required,
            // manual address entry above is always sufficient on its own.
            'customer_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'customer_longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * Guest checkout no longer sits behind the OTP/account gate, so this is
     * the replacement abuse guard: cap how many orders the same phone
     * number can place in a short window. Orders immediately decrement
     * real stock (see CheckoutController::store()), so unlimited rapid
     * checkout attempts aren't just a spam nuisance the way a contact-form
     * flood would be — they can actually deplete inventory a genuine
     * customer would otherwise have bought. Applies regardless of auth
     * state: an authenticated-but-unverified account has exactly as little
     * verified identity as a guest now that OTP isn't required first, so
     * there's no reason to only guard one of them. Layered on top of (not
     * instead of) the existing throttle:10,1 on this route, which limits
     * by IP — this catches the same actor spreading requests across
     * several IPs, which the IP throttle alone would miss.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $phone = $this->input('customer_phone');

            if (! $phone) {
                return;
            }

            $recentOrders = Order::where('customer_phone', $phone)
                ->where('created_at', '>=', now()->subMinutes(self::RATE_LIMIT_WINDOW_MINUTES))
                ->count();

            if ($recentOrders >= self::MAX_ORDERS_PER_PHONE) {
                $validator->errors()->add(
                    'customer_phone',
                    __('You\'ve placed several orders recently. Please wait a bit before placing another, or contact us directly if you need help.')
                );
            }
        });
    }
}
