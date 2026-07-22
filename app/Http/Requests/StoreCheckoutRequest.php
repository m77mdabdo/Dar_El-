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
     * Only fields actually rendered on the checkout page — no field is
     * validated here that the customer can't see and fill in.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
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

        // Guest-only: an authenticated account already had to prove control
        // of a real email/OTP flow at some point (or is a returning, known
        // customer), so a bot cost is only worth imposing on the anonymous
        // path this challenge actually targets. See CheckoutController::show()
        // for where the two operands are generated and session-stored.
        if (! $this->user()) {
            $rules['captcha_answer'] = ['required', 'integer'];
        }

        return $rules;
    }

    /**
     * The math-challenge check — the only thing withValidator() still does.
     * The phone/address rate limits used to live here too, but a FormRequest
     * runs entirely before the controller method does, so a check here can
     * never be atomic with the Order::create() call it's meant to gate: two
     * concurrent requests could both pass this check before either one's
     * order commits. That enforcement now lives in
     * CheckoutController::store() itself, inside the same locked section
     * that creates the order — see there for the full reasoning.
     */
    public function withValidator(Validator $validator): void
    {
        if ($this->user()) {
            return;
        }

        $validator->after(function (Validator $validator) {
            // pull(), not get(): consumed exactly once regardless of outcome,
            // so a failed attempt can never be retried against the same
            // answer — the redirect back to the checkout page (see
            // CheckoutController::store()'s error responses) always
            // generates a fresh challenge via a fresh GET to show().
            $expected = $this->session()->pull('checkout_captcha_answer');

            if ($expected === null || (int) $this->input('captcha_answer') !== (int) $expected) {
                $validator->errors()->add('captcha_answer', __('That answer isn\'t quite right — please try again.'));
            }
        });
    }
}
