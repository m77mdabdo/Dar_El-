<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown from inside CheckoutController::store()'s locked critical section
 * when either the phone- or address-based abuse guard trips. Carries which
 * form field the error belongs under so the two independent limits (phone
 * vs. delivery address) surface on the right input instead of both landing
 * on a generic message.
 */
class CheckoutRateLimitExceededException extends RuntimeException
{
    public function __construct(string $message, public readonly string $field)
    {
        parent::__construct($message);
    }
}
