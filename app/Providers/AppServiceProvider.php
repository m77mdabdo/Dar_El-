<?php

namespace App\Providers;

use App\Listeners\SendLoginAlertNotification;
use App\Services\CartService;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Login::class, SendLoginAlertNotification::class);

        View::composer('layouts.storefront', function ($view) {
            $view->with('cartCount', app(CartService::class)->count());
            $view->with('wishlistCount', auth()->check() ? auth()->user()->wishlists()->count() : 0);
        });

        View::composer('shop.partials.product-card', function ($view) {
            static $wishlistedIds = null;

            if ($wishlistedIds === null) {
                $wishlistedIds = auth()->check() ? auth()->user()->wishlists()->pluck('product_id')->all() : [];
            }

            $view->with('wishlistedIds', $wishlistedIds);
        });

        View::composer('admin.layout', function ($view) {
            $user = auth()->user();

            $view->with('notifUnreadCount', $user?->unreadNotifications()->count() ?? 0);
            $view->with('notifRecent', $user?->notifications()->latest()->take(6)->get() ?? collect());
        });
    }
}
