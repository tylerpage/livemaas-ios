<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Dog food amounts. Shown the way people measure with a scoop (1½, ¾), typed
 * however is natural ("1.5", "1 1/2", "1½").
 */
class Cups
{
    /** @var array<string, string> */
    private const array Fractions = ['0.25' => '¼', '0.33' => '⅓', '0.5' => '½', '0.67' => '⅔', '0.75' => '¾'];

    public static function format(float $cups, bool $withUnit = true): string
    {
        $whole = (int) floor($cups + 0.001);
        $fraction = round($cups - $whole, 2);
        $glyph = self::Fractions[(string) $fraction] ?? null;

        $number = match (true) {
            $fraction < 0.01 => (string) $whole,
            $glyph !== null => ($whole > 0 ? $whole : '').$glyph,
            default => rtrim(rtrim(number_format($cups, 2), '0'), '.'),
        };

        if (! $withUnit) {
            return $number;
        }

        return $number.' '.($cups > 0 && $cups <= 1.0 ? 'cup' : 'cups');
    }

    /**
     * Returns null for anything that is not a non-negative amount.
     */
    public static function parse(string $input): ?float
    {
        $clean = trim(str_ireplace(['cups', 'cup'], '', $input));
        $clean = strtr($clean, ['¼' => ' 1/4', '⅓' => ' 1/3', '½' => ' 1/2', '⅔' => ' 2/3', '¾' => ' 3/4']);
        $clean = trim(preg_replace('/\s+/', ' ', $clean) ?? '');

        if (preg_match('/^\d+(\.\d+)?$/', $clean)) {
            return round((float) $clean, 2);
        }

        if (preg_match('/^(?:(\d+) )?(\d+)\/(\d+)$/', $clean, $matches) && (int) $matches[3] > 0) {
            return round((int) $matches[1] + (int) $matches[2] / (int) $matches[3], 2);
        }

        return null;
    }
}
