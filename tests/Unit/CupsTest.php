<?php

declare(strict_types=1);

use App\Support\Cups;

it('shows amounts the way a scoop measures them', function (float $cups, string $expected): void {
    expect(Cups::format($cups))->toBe($expected);
})->with([
    [1.0, '1 cup'],
    [1.5, '1½ cups'],
    [0.25, '¼ cup'],
    [3.0, '3 cups'],
    [0.67, '⅔ cup'],
    [1.2, '1.2 cups'],
    [0.0, '0 cups'],
]);

it('reads amounts however they are typed', function (string $input, ?float $expected): void {
    expect(Cups::parse($input))->toBe($expected);
})->with([
    ['1.5', 1.5],
    ['1 1/2', 1.5],
    ['1½', 1.5],
    ['3/4', 0.75],
    ['2 cups', 2.0],
    ['1/3', 0.33],
    ['', null],
    ['lots', null],
    ['1/0', null],
]);
