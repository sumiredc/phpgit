<?php

declare(strict_types=1);

use Phpgit\Domain\Service\RefPattern;

describe('parsePath', function () {
    it(
        'match to reference',
        function (string $value, string $expected) {
            $actual = RefPattern::parsePath($value);

            expect($actual)->toBe($expected);
        }
    )
        ->with([
            ['ref: refs/heads/main', 'refs/heads/main'],
            ['ref: refs/heads/develop', 'refs/heads/develop'],
            ['ref: refs/heads/feature/commit-tree', 'refs/heads/feature/commit-tree'],
        ]);

    it(
        'returns to null when unmatch to reference',
        function (string $value) {
            $actual = RefPattern::parsePath($value);

            expect($actual)->toBeNull();
        }
    )
        ->with([
            'empty path' => ['ref: '],
            'blank' => [''],
            'invalid prefix' => ['refs: refs/heads/feature/commit-tree'],
            'no space' => ['ref:refs/heads/develop'],
        ]);
});
