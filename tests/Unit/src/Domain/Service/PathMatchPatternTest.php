<?php

declare(strict_types=1);

use Phpgit\Domain\Service\PathMatchPattern;

describe('matches', function () {
    it(
        'returns true when path matches pattern',
        function (string $path, string $pattern) {
            expect(PathMatchPattern::matches($path, $pattern))->toBeTrue();
        }
    )
        ->with([
            ['/foo/bar.txt', '/foo/*.txt'],
            ['/foo/bar/baz.txt', '/foo/*/*.txt'],
            ['src/App/Service.php', 'src/*/*.php'],
            ['logs/2025-01-01.log', 'logs/*.log'],
            ['index.html', '*.html'],
            ['', ''],
        ]);

    it(
        'returns false when path does not match pattern',
        function (string $path, string $pattern) {
            expect(PathMatchPattern::matches($path, $pattern))->toBeFalse();
        }
    )
        ->with([
            ['/foo/bar.txt', '/bar/*.txt'],
            ['/foo/bar/baz.txt', '/foo/*.txt'],
            ['src/App/Service.php', 'src/*.php'],
            ['logs/2025-01-01.log', '*.txt'],
            ['index.html', '*.js'],
            ['', '/foo/*.txt'],
            ['/foo/bar.txt', ''],
        ]);
});
