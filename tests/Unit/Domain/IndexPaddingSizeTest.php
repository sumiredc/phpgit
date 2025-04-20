<?php

declare(strict_types=1);

use Phpgit\Domain\IndexEntryPathSize;
use Phpgit\Domain\IndexEntrySize;
use Phpgit\Domain\IndexPaddingSize;

describe('new', function () {
    it('should match to properties', function (string $path, int $expected) {
        $size = IndexEntrySize::new(IndexEntryPathSize::new($path));
        $actual = IndexPaddingSize::new($size);

        expect($actual->value)->toBe($expected);
    })
        ->with([
            [str_repeat('a', 9), 0],
            [str_repeat('a', 8), 1],
            [str_repeat('a', 7), 2],
            [str_repeat('a', 6), 3],
            [str_repeat('a', 5), 4],
            [str_repeat('a', 4), 5],
            [str_repeat('a', 3), 6],
            [str_repeat('a', 2), 7],
            [str_repeat('a', 1), 0],
        ]);
});

describe('isEmpty', function () {
    it('should match to size zero', function (string $path, bool $expected) {
        $size = IndexEntrySize::new(IndexEntryPathSize::new($path));
        $actual = IndexPaddingSize::new($size);

        expect($actual->isEmpty())->toBe($expected);
    })
        ->with([
            [str_repeat('a', 9), true],
            [str_repeat('a', 8), false],
            [str_repeat('a', 7), false],
            [str_repeat('a', 6), false],
            [str_repeat('a', 5), false],
            [str_repeat('a', 4), false],
            [str_repeat('a', 3), false],
            [str_repeat('a', 2), false],
            [str_repeat('a', 1), true],
        ]);
});

describe('asPadding', function () {
    it('should match to padding string', function (string $path, string $expected) {
        $size = IndexEntrySize::new(IndexEntryPathSize::new($path));
        $actual = IndexPaddingSize::new($size);

        expect($actual->asPadding())->toBe($expected);
    })
        ->with([
            [str_repeat('a', 9), ''],
            [str_repeat('a', 8), "\0"],
            [str_repeat('a', 7), "\0\0"],
            [str_repeat('a', 6), "\0\0\0"],
            [str_repeat('a', 5), "\0\0\0\0"],
            [str_repeat('a', 4), "\0\0\0\0\0"],
            [str_repeat('a', 3), "\0\0\0\0\0\0"],
            [str_repeat('a', 2), "\0\0\0\0\0\0\0"],
            [str_repeat('a', 1), ''],
        ]);
});
