<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SocialAuthenticator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * Provider-agnostic OAuth redirect/callback. The {provider} route
 * parameter is whitelisted against config('services.oauth_providers'), so
 * enabling a new provider (Apple, Facebook, Microsoft, ...) is purely a
 * config change plus installing its Socialite driver — this controller and
 * SocialAuthenticator never need to change.
 */
class SocialAuthController extends Controller
{
    public function __construct(protected SocialAuthenticator $authenticator)
    {
    }

    public function redirect(Request $request, string $provider): RedirectResponse
    {
        $this->ensureProviderIsEnabled($provider);

        // Mirrors AuthenticatedSessionController@create's handling of a
        // JS-triggered auth prompt that can't rely on guest-redirect
        // intended-URL capture since it isn't a normal GET to a guarded route.
        $redirect = $request->string('redirect');
        if ($redirect->isNotEmpty() && Str::startsWith($redirect, '/') && ! Str::startsWith($redirect, '//')) {
            $request->session()->put('url.intended', url($redirect->value()));
        }

        if (config('app.debug')) {
            $config = config("services.{$provider}", []);
            logger()->info('Social OAuth config', [
                'provider' => $provider,
                'client_id' => $config['client_id'] ?? null,
                'client_secret_set' => ! empty($config['client_secret']),
                'redirect' => $config['redirect'] ?? null,
            ]);
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $this->ensureProviderIsEnabled($provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (Throwable $e) {
            Log::warning('Social auth callback failed', ['provider' => $provider, 'error' => $e->getMessage()]);

            return redirect()->route('login')->withErrors([
                'email' => __('Something went wrong signing you in. Please try again.'),
            ]);
        }

        if (! $socialUser->getEmail()) {
            return redirect()->route('login')->withErrors([
                'email' => __('We could not get your email address from your account. Please try a different sign-in method.'),
            ]);
        }

        [$user, $isNew] = $this->authenticator->findOrCreate($provider, $socialUser);

        if ($user->isDisabled()) {
            return redirect()->route('login')->withErrors([
                'email' => trans('auth.disabled'),
            ]);
        }

        // Read by SendLoginAlertNotification (fired synchronously by the
        // Login event below) so the alert records which provider this
        // specific login came through, without changing the Login event
        // itself or duplicating the alert-sending logic per provider.
        $request->session()->put('login_via_provider', $provider);

        Auth::login($user, true);
        $request->session()->regenerate();

        return $user->redirectResponseAfterAuth()
            ->with('status', $isNew
                ? __('Your account has been created successfully.')
                : __('Login successful.'));
    }

    protected function ensureProviderIsEnabled(string $provider): void
    {
        abort_unless(in_array($provider, config('services.oauth_providers', []), true), 404);
    }
}
