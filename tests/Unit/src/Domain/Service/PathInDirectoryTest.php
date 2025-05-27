<?php

declare(strict_types=1);

use Phpgit\Domain\Service\PathInDirectory;

describe('isUnder', function () {
    it(
        'returns to result of under a directory',
        function (string $path, string $dir, bool $expected) {
            $actual = PathInDirectory::isUnder($path, $dir);

            expect($actual)->toBe($expected);
        }
    )
        ->with([
            ['/src/main.go', '/src', true],
            ['/app/Controller/Controller.php', '/app/', true],
            ['/filedir/app.ts', '/file', false],
            ['/filename', '/filename', false],
            ['/README.md', '/', true],
        ]);

    it(
        'throws an exception on path is relative',
        function (string $path, Throwable $expected) {
            expect(fn() => PathInDirectory::isUnder($path, '/dir'))->toThrow($expected);
        }
    )
        ->with([
            ['relative/path', new InvalidArgumentException('Unfollow not absolute path: relative/path')]
        ]);

    it(
        'throws an exception on directory is relative',
        function (string $dir, Throwable $expected) {
            expect(fn() => PathInDirectory::isUnder('/path', $dir))->toThrow($expected);
        }
    )
        ->with([
            ['relative/dir', new InvalidArgumentException('Unfollow not absolute dir: relative/dir')]
        ]);
});
