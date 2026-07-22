<?php

namespace App\Support;

/**
 * Reduces every real-world way a customer might type the same Egyptian
 * phone number down to one comparable string, so a guest-checkout abuse
 * guard keyed on "this phone number" can't be defeated just by adding a
 * space, a dash, or swapping between local/international formatting on
 * each submission. Not a validator — it never rejects anything, just
 * folds format variants together; StoreCheckoutRequest's own rules still
 * decide whether a phone number is well-formed.
 */
class PhoneNumberNormalizer
{
    public static function normalize(string $phone): string
    {
        // Spaces, dashes, parens, dots, a leading "+" — none of them change
        // the real number, so "010 123-4567", "(010)1234567", and
        // "+20 10 123 4567" all reduce to the same digit string first.
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        // "0020" is the international trunk prefix for dialing out of a
        // country that still needs "00" before a country code (what a
        // "+" collapses to once non-digits are stripped) — drop just the
        // leading "00" so "0020XXXXXXXXXX" lines up with the "+20..." case
        // below instead of needing its own separate branch.
        if (str_starts_with($digits, '0020')) {
            $digits = substr($digits, 2);
        }

        // "20" + 10 digits is Egypt's country code plus a mobile number
        // with its local leading 0 dropped (the standard international
        // form) — convert back to the local "0XXXXXXXXXX" shape, which is
        // what most customers actually type and the shortest canonical
        // target to compare against.
        if (str_starts_with($digits, '20') && strlen($digits) === 12) {
            $digits = '0'.substr($digits, 2);
        }

        // A bare 10-digit number with no leading 0 at all (someone copied
        // just the subscriber number, or dropped the 0 out of habit from
        // typing international numbers) — restore the local trunk prefix.
        if (strlen($digits) === 10 && ! str_starts_with($digits, '0')) {
            $digits = '0'.$digits;
        }

        return $digits;
    }
}
