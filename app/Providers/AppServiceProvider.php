<?php

namespace App\Providers;

use App\Listeners\SendLoginAlertNotification;
use App\Services\CartService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
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

        // Laravel's default guest-middleware redirect (for an already
        // authenticated user re-visiting /login or /register) falls back to
        // route('home') since this app has no route literally named
        // 'dashboard' — which would send a logged-in admin to the
        // storefront instead of their own dashboard. Route by role instead.
        RedirectIfAuthenticated::redirectUsing(
            fn () => auth()->user()?->postLoginRedirectRoute() ?? route('home')
        );

        // Replaces only the *rendered content* of Laravel's built-in
        // ResetPassword notification with our branded master-layout email —
        // the notification class, token generation/validation, throttling,
        // and expiration are all still 100% Laravel's default machinery
        // (Notification::assertSentTo($user, ResetPassword::class) in
        // PasswordResetTest still passes unchanged).
        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject(__('emails.password_reset_subject'))
                ->view('emails.auth.password-reset', [
                    'user' => $notifiable,
                    'url' => $url,
                    'expiresInMinutes' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
                ]);
        });

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
