<?php

namespace App\Support;

class UserAgentParser
{
    /**
     * Lightweight, dependency-free browser/device label extraction for
     * display in security-facing emails. Not exhaustive — just enough to
     * show something recognizable like "Chrome on Windows" or "Safari on
     * iPhone" without adding a Composer package for it.
     */
    public static function browser(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'Unknown';
        }

        return match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera') => 'Opera',
            str_contains($userAgent, 'Chrome/') && ! str_contains($userAgent, 'Chromium') => 'Chrome',
            str_contains($userAgent, 'CriOS') => 'Chrome',
            str_contains($userAgent, 'FxiOS') => 'Firefox',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Safari/') && str_contains($userAgent, 'Version/') => 'Safari',
            default => 'Unknown',
        };
    }

    public static function device(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'Unknown';
        }

        return match (true) {
            str_contains($userAgent, 'iPhone') => 'iPhone',
            str_contains($userAgent, 'iPad') => 'iPad',
            str_contains($userAgent, 'Android') && str_contains($userAgent, 'Mobile') => 'Android Phone',
            str_contains($userAgent, 'Android') => 'Android Tablet',
            str_contains($userAgent, 'Macintosh') => 'Mac',
            str_contains($userAgent, 'Windows') => 'Windows PC',
            str_contains($userAgent, 'Linux') => 'Linux PC',
            default => 'Unknown',
        };
    }
}
