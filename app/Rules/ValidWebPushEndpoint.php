<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Restricts a Web Push subscription's `endpoint` to the real push services
 * browsers actually use — an allowlist, deliberately, not a blocklist of
 * "bad" hosts/IPs. A blocklist (reject localhost, reject private ranges,
 * reject 169.254.169.254, ...) can always be bypassed: DNS rebinding,
 * alternate IP encodings (decimal/octal/hex), redirects, IPv6 forms of the
 * same address, etc. An allowlist of exact real push-service hostnames
 * closes off every one of those at once, because an attacker cannot make
 * fcm.googleapis.com resolve to an internal service — so there is no
 * "bypass" to find. This is the fix for the SSRF risk where
 * PushNotificationService later makes a genuine server-side HTTP request
 * to whatever endpoint was stored here (see minishlink/web-push's
 * sendOneNotification()).
 */
class ValidWebPushEndpoint implements ValidationRule
{
    /**
     * Exact hostnames, or "*.suffix" wildcard patterns, for every browser's
     * real push service as of today. If a future browser introduces a new
     * push service host, subscriptions from it would be rejected until this
     * list is updated — a safe failure mode (subscribe silently not
     * working) rather than an unsafe one.
     */
    protected const ALLOWED_HOSTS = [
        'fcm.googleapis.com',
        'android.googleapis.com',
        'updates.push.services.mozilla.com',
        '*.notify.windows.com',
        '*.push.apple.com',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail(__('The :attribute must be a valid push service URL.'));

            return;
        }

        $parts = parse_url($value);

        if (! $parts || ($parts['scheme'] ?? null) !== 'https' || empty($parts['host'])) {
            $fail(__('The :attribute must be a valid push service URL.'));

            return;
        }

        $host = strtolower($parts['host']);

        // No legitimate push service is ever addressed by a raw IP literal
        // (v4 or v6) — ruling this out first means every private/loopback/
        // link-local/metadata address is rejected before the allowlist
        // check even runs, with no parsing of the address itself needed.
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $fail(__('This does not look like a supported push notification service.'));

            return;
        }

        foreach (self::ALLOWED_HOSTS as $pattern) {
            if (str_starts_with($pattern, '*.')) {
                // substr($pattern, 1) keeps the leading dot ("*.push.apple.com"
                // -> ".push.apple.com"), so this only matches a real
                // subdomain of the allowed host — never a look-alike like
                // "evil-push.apple.com.attacker.net", which ends with
                // ".attacker.net", not ".push.apple.com".
                if (str_ends_with($host, substr($pattern, 1))) {
                    return;
                }
            } elseif ($host === $pattern) {
                return;
            }
        }

        $fail(__('This does not look like a supported push notification service.'));
    }
}
