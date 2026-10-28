<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Dollars on screen, integer cents on the wire. Never do money maths in floats.
 */
class Money
{
    public static function format(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';

        return $sign.'$'.number_format(intdiv(abs($cents), 100)).'.'.str_pad((string) (abs($cents) % 100), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Parse what someone typed ("142.37", "$1,200", "15") into cents.
     * Returns null when it isn't a positive amount with at most two decimals.
     */
    public static function parse(string $input): ?int
    {
        $clean = str_replace(['$', ',', ' '], '', trim($input));

        if (! preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $clean, $matches)) {
            return null;
        }

        $cents = (int) $matches[1] * 100 + (int) str_pad($matches[2] ?? '0', 2, '0');

        return $cents > 0 ? $cents : null;
    }
}
