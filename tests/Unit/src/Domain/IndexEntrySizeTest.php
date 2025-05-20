<?php

declare(strict_types=1);

use Phpgit\Domain\IndexEntryPathSize;
use Phpgit\Domain\IndexEntrySize;

describe('new', function () {
    it('should match properties', function (string $path, int $expected) {
        $pathSize = IndexEntryPathSize::new($path);
        $actual = IndexEntrySize::new($pathSize);

        expect($actual->value)->toBe($expected);
    })
        ->with([
            [str_repeat('a', 1000), GIT_INDEX_ENTRY_HEADER_LENGTH + 1000 + 1],
            [str_repeat('a', 2000), GIT_INDEX_ENTRY_HEADER_LENGTH + 2000 + 1],
        ]);
});
