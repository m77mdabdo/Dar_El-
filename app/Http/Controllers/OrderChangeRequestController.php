<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderChangeRequest;
use App\Models\Setting;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderChangeRequestController extends Controller
{
    /**
     * One controller action serves all three entry points (account order
     * detail, account order tracker, guest order tracker) — see the
     * request-window partial for how each caller computes its own form
     * action URL. Security differs by caller, not by route:
     *   - Authenticated: ownership check against the order, strictly by
     *     user_id match — there is no admin bypass here (unlike the
     *     policy-based before() shortcut used elsewhere in the app), so an
     *     admin acting on a customer's order still goes through the same
     *     ownership check as anyone else and is rejected unless they also
     *     own the order.
     *   - Guest: the form's action URL is itself a signed URL minted by
     *     OrderTrackingController::show() (same pattern as
     *     back-in-stock.unsubscribe/invoice.download) — Laravel validates
     *     the signature against this exact request regardless of HTTP
     *     method, so hasValidSignature() is the entire guest-safe check.
     *
     * Validates manually (not $request->validate()) for the same reason as
     * BackInStockSubscriptionController::store() — this isn't an api/*
     * route, so a ValidationException wouldn't auto-render as JSON, and
     * this form is submitted via fetch() either way.
     */
    public function store(Request $request, Order $order): JsonResponse
    {
        if ($request->user()) {
            abort_unless($order->user_id === $request->user()->id, 403);
        } else {
            abort_unless($request->hasValidSignature(), 403);
        }

        $order->loadMissing(['items', 'statusHistories']);

        $window = $this->resolveWindow($order);

        if (! $window) {
            return response()->json([
                'errors' => ['type' => [__('order_change_requests.window_closed')]],
            ], 422);
        }

        $allowedTypes = $window === 'pending'
            ? OrderChangeRequest::PENDING_WINDOW_TYPES
            : OrderChangeRequest::DELIVERED_WINDOW_TYPES;

        $validItemIds = $order->items->pluck('id')->all();

        $validator = Validator::make($request->all(), [
            'type' => ['required', 'string', 'in:'.implode(',', $allowedTypes)],
            'order_item_ids' => ['nullable', 'array'],
            'order_item_ids.*' => ['integer', 'in:'.implode(',', $validItemIds ?: [0])],
            'reason' => ['required', 'string', 'in:'.implode(',', OrderChangeRequest::REASONS)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'desired_variant' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        // A lightweight spam/duplicate guard, not a hard business rule —
        // one open request per order at a time is enough to stop a customer
        // (or an abuser working around the throttle) from flooding the same
        // order's WhatsApp thread with near-identical requests. Resolved or
        // already-contacted requests don't block a genuinely new one.
        //
        // Enforced by the database, not a check-then-create in app code: a
        // "does one already exist?" query followed by a separate insert is a
        // classic TOCTOU race (two concurrent submissions can both see "no
        // pending row" and both insert). OrderChangeRequest's pending_order_id
        // column + its unique index (see the migration and the model's
        // saving() hook) make a second pending row for the same order
        // physically impossible to insert, regardless of timing — so this
        // just attempts the create and translates the resulting unique-
        // constraint violation into the same friendly error.
        try {
            $changeRequest = OrderChangeRequest::create([
                'order_id' => $order->id,
                'type' => $validated['type'],
                'order_item_ids' => $validated['order_item_ids'] ?? null,
                'reason' => $validated['reason'],
                'notes' => $validated['notes'] ?? null,
                'desired_variant' => $validated['desired_variant'] ?? null,
            ]);
        } catch (QueryException $e) {
            // SQLSTATE 23000 is "integrity constraint violation" on both
            // MySQL (production) and SQLite (tests) — the only constraint
            // this insert could violate is pending_order_id's unique index,
            // since order_id itself is guaranteed valid by route-model
            // binding above.
            if (($e->errorInfo[0] ?? null) !== '23000') {
                throw $e;
            }

            return response()->json([
                'errors' => ['type' => [__('order_change_requests.already_pending')]],
            ], 422);
        }

        return response()->json([
            'status' => 'ok',
            'message' => __('order_change_requests.submitted'),
            'whatsapp_url' => $this->buildWhatsAppUrl($order, $changeRequest),
        ]);
    }

    /**
     * Which window (if any) currently permits a request — the single
     * server-side source of truth the frontend's own show/hide logic
     * mirrors, so a customer can't submit a stale request type after their
     * order moved past pending, or after the 3-day exchange window lapsed,
     * just by replaying an old form payload.
     */
    protected function resolveWindow(Order $order): ?string
    {
        if ($order->status === 'pending') {
            return 'pending';
        }

        if ($order->status === 'delivered') {
            $deliveredAt = $order->deliveredAt();

            if ($deliveredAt && $deliveredAt->copy()->addDays(3)->isFuture()) {
                return 'delivered';
            }
        }

        return null;
    }

    protected function buildWhatsAppUrl(Order $order, OrderChangeRequest $changeRequest): ?string
    {
        $whatsapp = Setting::get('whatsapp_number');

        if (! $whatsapp) {
            return null;
        }

        $itemsLabel = __('order_change_requests.whole_order');

        if (! empty($changeRequest->order_item_ids)) {
            $itemsLabel = $order->items
                ->whereIn('id', $changeRequest->order_item_ids)
                ->map(fn ($item) => $item->product ? trans_field($item->product, 'name') : $item->product_name)
                ->implode('، ');
        }

        $lines = [
            __('order_change_requests.whatsapp_greeting'),
            '',
            __('order_change_requests.whatsapp_order_number', ['number' => $order->order_number]),
            __('order_change_requests.whatsapp_customer_name', ['name' => $order->customer_name]),
            __('order_change_requests.whatsapp_type', ['type' => __('order_change_requests.type_'.$changeRequest->type)]),
            __('order_change_requests.whatsapp_items', ['items' => $itemsLabel]),
            __('order_change_requests.whatsapp_reason', ['reason' => __('order_change_requests.reason_'.$changeRequest->reason)]),
            __('order_change_requests.whatsapp_notes', ['notes' => $changeRequest->notes ?: __('order_change_requests.no_notes')]),
        ];

        if ($changeRequest->type === OrderChangeRequest::TYPE_EXCHANGE && $changeRequest->desired_variant) {
            $lines[] = __('order_change_requests.whatsapp_desired_variant', ['variant' => $changeRequest->desired_variant]);
        }

        $text = implode("\n", $lines);

        return 'https://wa.me/'.preg_replace('/[^0-9]/', '', $whatsapp).'?text='.rawurlencode($text);
    }
}
