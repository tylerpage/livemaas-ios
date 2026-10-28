<?php

declare(strict_types=1);

use App\Support\Money;

it('formats cents as dollars', function (int $cents, string $expected): void {
    expect(Money::format($cents))->toBe($expected);
})->with([
    [14_237, '$142.37'],
    [5, '$0.05'],
    [123_456_700, '$1,234,567.00'],
    [-2_500, '-$25.00'],
]);

it('parses what people type into cents', function (string $input, ?int $expected): void {
    expect(Money::parse($input))->toBe($expected);
})->with([
    ['142.37', 14_237],
    ['$1,200', 120_000],
    ['15', 1_500],
    ['15.5', 1_550],
    [' 0.99 ', 99],
    ['0', null],
    ['12.345', null],
    ['abc', null],
    ['', null],
    ['-5', null],
]);
