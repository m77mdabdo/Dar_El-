<?php

use App\Http\Controllers\Account\AddressController;
use App\Http\Controllers\Account\BlogCommentController as AccountBlogCommentController;
use App\Http\Controllers\Account\OrderController as AccountOrderController;
use App\Http\Controllers\Account\ReviewController as AccountReviewController;
use App\Http\Controllers\BackInStockSubscriptionController;
use App\Http\Controllers\BlogCommentController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvoiceDownloadController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\OrderTrackingController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// Precached by the service worker at install time and served from Cache
// Storage (no PHP involved) when a navigation fails with no network — see
// public/sw.js. Visiting it directly while online just shows the same
// branded page server-rendered normally.
Route::view('/offline', 'errors.offline')->name('offline');

// Signed, guest-safe invoice link used in customer emails — works whether
// or not the customer has (or is logged into) an account, unlike the
// auth+ownership-gated account.orders.invoice route.
Route::get('/invoice/{order}/download', [InvoiceDownloadController::class, 'show'])
    ->name('invoice.download')
    ->middleware('signed');

// Public order tracking. lookup() verifies order_number + email/phone and
// mints the signed URL to show() — same guest-safe, signature-is-the-only-
// security convention as invoice.download above. The logged-in customer's
// own equivalent (account.orders.track, ownership-checked via OrderPolicy
// instead of a signature) lives in the account.* group further down.
Route::get('/track-order', [OrderTrackingController::class, 'form'])->name('track-order.form');
Route::post('/track-order', [OrderTrackingController::class, 'lookup'])
    ->middleware('throttle:10,1')
    ->name('track-order.lookup');
Route::get('/track-order/{order}', [OrderTrackingController::class, 'show'])
    ->name('track-order.show')
    ->middleware('signed');

Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/shop/{product:slug}', [ShopController::class, 'show'])->name('shop.show');

Route::post('/products/{product}/notify-me', [BackInStockSubscriptionController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('back-in-stock.store');

// Signed, guest-safe unsubscribe link used in back-in-stock emails — same
// convention as invoice.download: security comes entirely from the URL
// signature, not a session, so this works for guest subscribers too.
Route::get('/notify-me/unsubscribe/{subscription}', [BackInStockSubscriptionController::class, 'unsubscribe'])
    ->middleware('signed')
    ->name('back-in-stock.unsubscribe');

// Navbar live-search preview. Higher throttle ceiling than the site's other
// throttled endpoints (contact/newsletter/reviews at 10/min) — this is a
// read-only, debounced-as-you-type endpoint, not an infrequent form submit.
Route::get('/search/live', [SearchController::class, 'live'])->middleware('throttle:60,1')->name('search.live');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');

Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/services', [PageController::class, 'services'])->name('services');

Route::get('/contact', [ContactController::class, 'show'])->name('contact.show');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:10,1')->name('contact.store');

Route::post('/newsletter', [NewsletterController::class, 'store'])->middleware('throttle:10,1')->name('newsletter.store');

Route::post('/reviews/{review}/helpful', [ReviewController::class, 'markHelpful'])->middleware('throttle:10,1')->name('reviews.helpful');

Route::get('/lang/{locale}', function (string $locale) {
    if (in_array($locale, ['en', 'ar'], true)) {
        session(['locale' => $locale]);
    }

    return back();
})->name('lang.switch');

Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/{product:slug}', [CartController::class, 'add'])->name('add');
    Route::patch('/{key}', [CartController::class, 'update'])->name('update');
    Route::delete('/{key}', [CartController::class, 'remove'])->name('remove');
    Route::post('/coupon/apply', [CartController::class, 'applyCoupon'])->middleware('throttle:10,1')->name('coupon.apply');
    Route::delete('/coupon/remove', [CartController::class, 'removeCoupon'])->name('coupon.remove');
});

Route::prefix('checkout')->name('checkout.')->middleware('verified.if.auth')->group(function () {
    Route::get('/', [CheckoutController::class, 'show'])->name('show');
    Route::post('/', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('store');
    Route::get('/{order}/success', [CheckoutController::class, 'success'])->name('success');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('wishlist')->name('wishlist.')->group(function () {
        Route::get('/', [WishlistController::class, 'index'])->name('index');
        Route::post('/{product:slug}', [WishlistController::class, 'store'])->name('add');
        Route::delete('/{product:slug}', [WishlistController::class, 'destroy'])->name('remove');
        Route::post('/{product:slug}/move-to-cart', [WishlistController::class, 'moveToCart'])->name('move');
    });

    Route::post('/shop/{product:slug}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::patch('/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

    Route::post('/blog/{post:slug}/comments', [BlogCommentController::class, 'store'])->name('blog.comments.store');
    Route::patch('/blog-comments/{comment}', [BlogCommentController::class, 'update'])->name('blog.comments.update');
    Route::delete('/blog-comments/{comment}', [BlogCommentController::class, 'destroy'])->name('blog.comments.destroy');
});

Route::prefix('account')->name('account.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/orders', [AccountOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AccountOrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/track', [AccountOrderController::class, 'track'])->name('orders.track');
    Route::get('/orders/{order}/invoice', [AccountOrderController::class, 'invoice'])->name('orders.invoice');

    Route::get('/reviews', [AccountReviewController::class, 'index'])->name('reviews.index');
    Route::get('/blog-comments', [AccountBlogCommentController::class, 'index'])->name('blog-comments.index');

    Route::resource('addresses', AddressController::class)->except(['show']);
});

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
