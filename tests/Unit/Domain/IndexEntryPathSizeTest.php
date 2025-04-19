<?php

declare(strict_types=1);

use Phpgit\Domain\IndexEntryPathSize;

describe('new', function () {
    it('match to path length', function (string $path, int $expected) {
        $actual = IndexEntryPathSize::new($path);

        expect($actual->value)->toBe($expected);
        expect($actual->withNull)->toBe($expected + 1);
    })
        ->with([
            ['README.md', 9],
            ['src/domain/valueobject/user_id.go', 33],
        ]);
});

describe('parse', function () {
    it('parse value in flags match value', function (int $flags, int $expected) {
        $actual = IndexEntryPathSize::parse($flags);

        expect($actual->value)->toBe($expected);
    })
        ->with([
            [(0 << 12 | 999), 999],
        ]);
});

describe('isOverFlagsSpace', function () {
    it('cannot save the path size', function (string $path, bool $expected) {
        $actual = IndexEntryPathSize::new($path);

        expect($actual->isOverFlagsSpace())->toBe($expected);
    })
        ->with([
            'not over' => [str_repeat('a', 4095), false],
            'is over' => [str_repeat('a', 4096), true]
        ]);
});

describe('asStorableValue', function () {
    it('returns to saveable value', function (string $path, int $expected) {
        $actual = IndexEntryPathSize::new($path);

        expect($actual->asStorableValue())->toBe($expected);
    })
        ->with([
            'actual path size' => [str_repeat('a', 4094), 4094],
            'upper limit value' => [str_repeat('a', 4096), 4095],
        ]);
});
