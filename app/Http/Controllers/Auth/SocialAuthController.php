<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SocialAuthenticator;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
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

        $configuredRedirect = (string) (config("services.{$provider}.redirect") ?? '');

        // Root-cause guard for the exact "Google sends me to localhost"
        // production bug: that happens when the *.env* redirect URL is a
        // local-dev value, not a code path — this can't be fixed by code
        // alone, but the app must never silently start an OAuth flow that
        // is guaranteed to strand the user, so refuse and log loudly
        // instead of sending them to Google only to bounce off localhost
        // on the way back.
        if (app()->environment('production') && $this->pointsAtLocalHost($configuredRedirect)) {
            Log::critical('OAuth misconfigured: redirect URL points at localhost in production', [
                'provider' => $provider,
                'configured_redirect' => $configuredRedirect,
                'app_url' => config('app.url'),
            ]);

            return redirect()->route('login')->withErrors([
                'email' => __('Sign-in with :provider is temporarily unavailable. Please use your email and password, or try again shortly.', ['provider' => ucfirst($provider)]),
            ]);
        }

        if (config('app.debug')) {
            logger()->info('Social OAuth redirect', [
                'provider' => $provider,
                'client_id_set' => ! empty(config("services.{$provider}.client_id")),
                'client_secret_set' => ! empty(config("services.{$provider}.client_secret")),
                'redirect' => $configuredRedirect,
            ]);
        }

        return Socialite::driver($provider)->redirect();
    }

    protected function pointsAtLocalHost(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return in_array($host, [null, '', 'localhost', '127.0.0.1'], true);
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $this->ensureProviderIsEnabled($provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (InvalidStateException $e) {
            // The single most common real-world cause: the browser didn't
            // send back the same session cookie that was present when
            // redirect() ran — a session-cookie/domain/HTTPS-detection
            // mismatch, not anything about the user's Google account. Log
            // enough to diagnose *that* without ever logging the request's
            // actual cookie/token values.
            Log::warning('Social auth callback failed: session state mismatch', [
                'provider' => $provider,
                'exception' => InvalidStateException::class,
                'route' => $request->route()?->getName(),
                'callback_url' => $request->fullUrl(),
                'app_url' => config('app.url'),
                'had_session_id' => $request->hasSession() && $request->session()->isStarted(),
            ]);

            return redirect()->route('login')->withErrors([
                'email' => __('Your sign-in session expired before Google could confirm it. Please try again.'),
            ]);
        } catch (RequestException $e) {
            Log::error('Social auth callback failed: provider API error', [
                'provider' => $provider,
                'exception' => RequestException::class,
                'status' => $e->getResponse()?->getStatusCode(),
                'route' => $request->route()?->getName(),
            ]);

            return redirect()->route('login')->withErrors([
                'email' => __('Something went wrong signing you in. Please try again.'),
            ]);
        } catch (Throwable $e) {
            Log::warning('Social auth callback failed', [
                'provider' => $provider,
                'exception' => $e::class,
                'error' => $e->getMessage(),
                'route' => $request->route()?->getName(),
                'callback_url' => $request->fullUrl(),
                'app_url' => config('app.url'),
            ]);

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
