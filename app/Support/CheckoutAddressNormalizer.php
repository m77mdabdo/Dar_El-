<?php

namespace App\Support;

/**
 * Reduces a delivery address (governorate + city + free-text address) down
 * to one comparable key, for the same reason PhoneNumberNormalizer exists:
 * an abuse guard keyed on "this delivery address" shouldn't be defeatable
 * by capitalization, extra whitespace, or incidental punctuation across
 * otherwise-identical submissions. The three components are combined
 * (rather than compared separately) because the guard cares about one real-
 * world destination, not any single field in isolation — and hashed, since
 * the result is only ever used as an equality-comparable rate-limit key,
 * never displayed or reversed.
 */
class CheckoutAddressNormalizer
{
    public static function key(string $governorate, string $city, string $address): string
    {
        $normalized = implode('|', array_map(
            [self::class, 'normalizeComponent'],
            [$governorate, $city, $address]
        ));

        return hash('sha256', $normalized);
    }

    private static function normalizeComponent(string $value): string
    {
        // mb_strtolower is a no-op on Arabic script (no case distinction)
        // and safely folds any Latin-script input customers mix in.
        $value = mb_strtolower(trim($value));

        // Common incidental punctuation ("Cairo, Nasr City." vs "cairo
        // nasr city") shouldn't make two submissions of the same address
        // look different — collapsed to spaces, then whitespace runs
        // collapsed to one space each.
        $value = preg_replace('/[.,\/#!$%\^&\*;:{}=\-_`~()]/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }
}
