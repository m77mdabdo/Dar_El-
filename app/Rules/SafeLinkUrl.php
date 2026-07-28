<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Restricts a banner/CTA link_url to either a relative path (no scheme
 * at all — the common case, e.g. "/shop") or an absolute http(s) URL.
 * Rejects any other scheme (javascript:, data:, vbscript:, ...), which
 * would otherwise execute in a storefront visitor's browser the moment
 * they click the link — a stored-XSS vector reachable by any staff
 * account with banners.manage, not just a hardening exercise (2026-07
 * audit finding). Shared across every Banner type (hero/offer/
 * collection/category all use the same link_url column), not just
 * Hero Banners' own admin screen.
 */
class SafeLinkUrl implements ValidationRule
{
    protected const ALLOWED_SCHEMES = ['http', 'https'];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        if (! in_array($this->scheme($value), [null, ...self::ALLOWED_SCHEMES], true)) {
            $fail(__('The :attribute must be a relative path or an http(s) URL.'));
        }
    }

    /**
     * A browser strips leading/trailing whitespace and embedded
     * tab/CR/LF from a URL before interpreting its scheme (WHATWG URL
     * spec) — parse_url() does not, so "  javascript:..." or
     * "java\tscript:..." would otherwise slip through as "no scheme"
     * while a browser still executes them. Normalizing first closes
     * that bypass.
     */
    protected function scheme(string $value): ?string
    {
        $normalized = preg_replace('/^[\x00-\x20]+|[\x00-\x20]+$/u', '', $value);
        $normalized = str_replace(["\t", "\r", "\n"], '', $normalized ?? $value);

        $scheme = parse_url($normalized, PHP_URL_SCHEME);

        return $scheme ? strtolower($scheme) : null;
    }
}
