<?php

use App\Http\Controllers\Account\OrderController as AccountOrderController;
use App\Http\Controllers\Admin\BlogCommentController;
use App\Http\Controllers\Admin\BlogPostController;
use App\Http\Controllers\Admin\CartController as AdminCartController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmailPreviewController;
use App\Http\Controllers\Admin\NewsletterController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OrderChangeRequestController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ProductBulkActionController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductOptionController;
use App\Http\Controllers\Admin\ProductOptionValueController;
use App\Http\Controllers\Admin\ProductVariantBulkActionController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::redirect('/', '/admin/dashboard');
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('products', ProductController::class)->except(['show']);
    Route::post('products/bulk-action', [ProductBulkActionController::class, 'handle'])->name('products.bulk-action');
    Route::patch('products/{product}/autosave', [ProductController::class, 'autosave'])->name('products.autosave');
    Route::patch('products/{product}/images/reorder', [ProductController::class, 'reorderImages'])->name('products.images.reorder');
    Route::patch('products/{product}/images/{image}', [ProductController::class, 'updateImage'])->name('products.images.update');
    Route::patch('products/{product}/images/{image}/cover', [ProductController::class, 'setCoverImage'])->name('products.images.cover');
    Route::delete('products/{product}/images/{image}', [ProductController::class, 'destroyImage'])->name('products.images.destroy');

    Route::prefix('products/{product}')->name('products.')->group(function () {
        Route::patch('sizes', [ProductController::class, 'updateSizes'])->name('sizes.update');

        Route::post('options', [ProductOptionController::class, 'store'])->name('options.store');
        Route::patch('options/{option}', [ProductOptionController::class, 'update'])->name('options.update');
        Route::delete('options/{option}', [ProductOptionController::class, 'destroy'])->name('options.destroy');

        Route::post('options/{option}/values', [ProductOptionValueController::class, 'store'])->name('options.values.store');
        Route::patch('options/{option}/values/{value}', [ProductOptionValueController::class, 'update'])->name('options.values.update');
        Route::delete('options/{option}/values/{value}', [ProductOptionValueController::class, 'destroy'])->name('options.values.destroy');
        Route::post('options/{option}/values/{value}/images', [ProductOptionValueController::class, 'storeImages'])->name('options.values.images.store');
        Route::delete('options/{option}/values/{value}/images/{image}', [ProductOptionValueController::class, 'destroyImage'])->name('options.values.images.destroy');

        Route::post('variants/generate', [ProductVariantController::class, 'generate'])->name('variants.generate');
        Route::patch('variants/bulk', [ProductVariantController::class, 'bulkUpdate'])->name('variants.bulk');
        Route::post('variants/bulk-action', [ProductVariantBulkActionController::class, 'handle'])->name('variants.bulk-action');
        Route::patch('variants/{variant}', [ProductVariantController::class, 'update'])->name('variants.update');
        Route::delete('variants/{variant}', [ProductVariantController::class, 'destroy'])->name('variants.destroy');
    });

    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('coupons', CouponController::class)->except(['show']);
    Route::resource('blog', BlogPostController::class)->parameters(['blog' => 'post'])->except(['show']);

    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
    Route::get('orders/{order}/invoice', [AccountOrderController::class, 'invoice'])->name('orders.invoice');

    Route::get('order-change-requests', [OrderChangeRequestController::class, 'index'])->name('order-change-requests.index')->middleware('admin.permission:orders.view');
    Route::patch('order-change-requests/{orderChangeRequest}/status', [OrderChangeRequestController::class, 'updateStatus'])->name('order-change-requests.status')->middleware('admin.permission:orders.update_status');

    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::get('{customer}', [CustomerController::class, 'show'])->name('show');
        Route::get('{customer}/orders', [CustomerController::class, 'orders'])->name('orders');
        Route::get('{customer}/carts', [CustomerController::class, 'carts'])->name('carts');
        Route::get('{customer}/wishlist', [CustomerController::class, 'wishlist'])->name('wishlist');
        Route::post('{customer}/notes', [CustomerController::class, 'addNote'])->name('notes.store');
        Route::patch('{customer}/toggle-disabled', [CustomerController::class, 'toggleDisabled'])->name('toggle-disabled');
        Route::post('{customer}/send-reminder', [CustomerController::class, 'sendReminder'])->name('send-reminder');
        Route::delete('{customer}', [CustomerController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('carts')->name('carts.')->middleware('admin.permission:carts.view')->group(function () {
        Route::get('/', [AdminCartController::class, 'index'])->name('index');
        Route::post('send-bulk-reminders', [AdminCartController::class, 'bulkReminder'])->name('bulkReminder')->middleware('admin.permission:carts.send_reminder');
        Route::get('{cart}', [AdminCartController::class, 'show'])->name('show');
        Route::post('{cart}/send-reminder', [AdminCartController::class, 'sendReminder'])->name('sendReminder')->middleware('admin.permission:carts.send_reminder');
    });

    Route::get('reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::get('reviews/{review}', [ReviewController::class, 'show'])->name('reviews.show');
    Route::patch('reviews/{review}/approve', [ReviewController::class, 'approve'])->name('reviews.approve');
    Route::patch('reviews/{review}/reject', [ReviewController::class, 'reject'])->name('reviews.reject');
    Route::patch('reviews/{review}/feature', [ReviewController::class, 'feature'])->name('reviews.feature');
    Route::patch('reviews/{review}/unfeature', [ReviewController::class, 'unfeature'])->name('reviews.unfeature');
    Route::delete('reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

    Route::get('blog-comments', [BlogCommentController::class, 'index'])->name('blog-comments.index');
    Route::get('blog-comments/{comment}', [BlogCommentController::class, 'show'])->name('blog-comments.show');
    Route::patch('blog-comments/{comment}/approve', [BlogCommentController::class, 'approve'])->name('blog-comments.approve');
    Route::patch('blog-comments/{comment}/reject', [BlogCommentController::class, 'reject'])->name('blog-comments.reject');
    Route::delete('blog-comments/{comment}', [BlogCommentController::class, 'destroy'])->name('blog-comments.destroy');

    Route::get('contact-messages', [ContactMessageController::class, 'index'])->name('contact-messages.index')->middleware('admin.permission:messages.view');
    Route::patch('contact-messages/{contactMessage}/read', [ContactMessageController::class, 'markRead'])->name('contact-messages.read')->middleware('admin.permission:messages.reply');

    Route::get('newsletter', [NewsletterController::class, 'index'])->name('newsletter.index')->middleware('admin.permission:newsletter.view');
    Route::get('newsletter/export', [NewsletterController::class, 'export'])->name('newsletter.export')->middleware('admin.permission:newsletter.view');

    Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit')->middleware('admin.permission:settings.view');
    Route::patch('settings', [SettingController::class, 'update'])->name('settings.update')->middleware('admin.permission:settings.edit');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index')->middleware('admin.permission:notifications.view');
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read')->middleware('admin.permission:notifications.view');
    Route::patch('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all')->middleware('admin.permission:notifications.view');

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::get('email-preview/{type}', [EmailPreviewController::class, 'show'])->name('email-preview.show');

    Route::middleware('super_admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::post('users/{user}/force-logout', [UserController::class, 'forceLogout'])->name('users.force-logout');

        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('roles/{role}', [RoleController::class, 'show'])->name('roles.show');
        Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
    });
});
