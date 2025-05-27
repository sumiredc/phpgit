<?php

declare(strict_types=1);

use Phpgit\Domain\Service\PathPattern;

describe('is', function () {
    it(
        'returns true when string contains wildcard pattern characters',
        function (string $value) {
            expect(PathPattern::is($value))->toBeTrue();
        }
    )
        ->with([
            ['*'],
            ['foo*'],
            ['file?.txt'],
            ['[abc]'],
            ['{a,b}'],
            ['prefix-{version}.zip'],
            ['src/[a-z]/file.php'],
        ]);

    it(
        'returns false when string does not contain pattern characters',
        function (string $value) {
            expect(PathPattern::is($value))->toBeFalse();
        }
    )
        ->with([
            [''],
            ['foobar'],
            ['file.txt'],
            ['path/to/file'],
            ['2025-01-01.log'],
            ['src/App/Service.php'],
        ]);
});
