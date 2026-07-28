<?php

namespace App\Support;

/**
 * CSV formula-injection guard (OWASP CSV Injection). Any cell value that
 * begins with =, +, -, @, a tab, or a carriage return is interpreted as
 * a formula by Excel/LibreOffice/Google Sheets when the exported file is
 * opened — ranging from a phishing HYPERLINK() to legacy DDE command
 * execution. Untrusted input (e.g. a customer's checkout name) can start
 * with any of these. Prefixing with a single quote neutralizes the
 * formula interpretation while leaving the visible text unchanged in
 * every spreadsheet application that opens the file.
 */
class Csv
{
    protected const DANGEROUS_PREFIXES = ['=', '+', '-', '@', "\t", "\r"];

    public static function safeCell(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        foreach (self::DANGEROUS_PREFIXES as $prefix) {
            if (str_starts_with($value, $prefix)) {
                return "'".$value;
            }
        }

        return $value;
    }

    /**
     * @param  array<int, mixed>  $row
     * @return array<int, mixed>
     */
    public static function safeRow(array $row): array
    {
        return array_map(self::safeCell(...), $row);
    }
}
