<?php

use App\Http\Controllers\Account\OrderController as AccountOrderController;
use App\Http\Controllers\Admin\BlogPostController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\NewsletterController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SettingController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::redirect('/', '/admin/dashboard');
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('products', ProductController::class)->except(['show']);
    Route::patch('products/{product}/images/{image}', [ProductController::class, 'updateImage'])->name('products.images.update');
    Route::delete('products/{product}/images/{image}', [ProductController::class, 'destroyImage'])->name('products.images.destroy');
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('coupons', CouponController::class)->except(['show']);
    Route::resource('blog', BlogPostController::class)->parameters(['blog' => 'post'])->except(['show']);

    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
    Route::get('orders/{order}/invoice', [AccountOrderController::class, 'invoice'])->name('orders.invoice');

    Route::get('reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::patch('reviews/{review}/approve', [ReviewController::class, 'approve'])->name('reviews.approve');
    Route::patch('reviews/{review}/reject', [ReviewController::class, 'reject'])->name('reviews.reject');

    Route::get('contact-messages', [ContactMessageController::class, 'index'])->name('contact-messages.index');
    Route::patch('contact-messages/{contactMessage}/read', [ContactMessageController::class, 'markRead'])->name('contact-messages.read');

    Route::get('newsletter', [NewsletterController::class, 'index'])->name('newsletter.index');
    Route::get('newsletter/export', [NewsletterController::class, 'export'])->name('newsletter.export');

    Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::patch('settings', [SettingController::class, 'update'])->name('settings.update');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::patch('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});
