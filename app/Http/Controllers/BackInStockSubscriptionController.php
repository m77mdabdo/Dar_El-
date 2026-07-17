<?php

namespace App\Http\Controllers;

use App\Models\BackInStockSubscription;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

        $alreadySubscribed = BackInStockSubscription::where('product_id', $product->id)
            ->where('product_size_id', $productSizeId)
            ->where('email', $validated['email'])
            ->exists();

        if (! $alreadySubscribed) {
            BackInStockSubscription::create([
                'product_id' => $product->id,
                'product_size_id' => $productSizeId,
                'email' => $validated['email'],
                'user_id' => $request->user()?->id,
            ]);
        }

        // Both the fresh-signup and already-subscribed cases return the same
        // friendly, successful-looking response — a customer who signed up
        // last week and forgot shouldn't see anything that reads as an error.
        return response()->json([
            'status' => 'ok',
            'message' => $alreadySubscribed
                ? __("You're already on the list for this — we'll email you the moment it's back.")
                : __("We'll notify you as soon as it's back in stock."),
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
