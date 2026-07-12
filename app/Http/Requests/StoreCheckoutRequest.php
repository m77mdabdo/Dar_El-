<?php

namespace App\Http\Requests;

use App\Models\Order;
use App\Models\ShippingMethod;
use Illuminate\Contracts\Validation\ValidationRule;
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
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
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
}
