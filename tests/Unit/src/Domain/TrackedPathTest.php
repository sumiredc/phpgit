<?php

declare(strict_types=1);

use Phpgit\Domain\TrackedPath;

describe('parse', function () {
    it(
        'initializes and matches value proerty to resolve path from tracking root',
        function (string $path, string $expected) {
            $actual = TrackedPath::parse($path);

            expect($actual->value)->toBe($expected);
        }
    )
        ->with([
            ['README.md', 'README.md'],
            ['src/main.go', 'src/main.go'],
            ['src/user/../http/handler.go', 'src/http/handler.go'],
            ['/tmp/project/full/path/file.js', 'full/path/file.js'],
            ['/tmp/project/full/path/directory/', 'full/path/directory/'],
            ['/tmp/project/return/../src/path.c', 'src/path.c'],
            ['/root/../tmp/project/koko.rs', 'koko.rs'],
            ['.', ''],
        ]);

    it(
        'throws an exception, on unresolve path or outside of the repository',
        function (string $path, Throwable $expected) {
            expect(fn() => TrackedPath::parse($path))->toThrow($expected);
        }
    )
        ->with([

            [
                'src/../../main.go',
                new InvalidArgumentException(
                    'The specified path "src/../../main.go" is outside of the repository'
                )
            ],
            [
                '/tmp/full/path/file.js',
                new InvalidArgumentException(
                    'The specified path "/tmp/full/path/file.js" is outside of the repository'
                )
            ],

            [
                '../README.md',
                new InvalidArgumentException(
                    'The specified path "../README.md" is outside of the repository'
                )
            ],
            [
                '~/home.kt',
                new InvalidArgumentException(
                    'The specified path "~/home.kt" is outside of the repository'
                )
            ],
            [
                '/tmp/project/../../../src/path.c',
                new InvalidArgumentException(
                    'Invalid path traversal detected: "/tmp/project/../../../src/path.c" escapes the repository root'
                )
            ],
        ]);
});

describe('full', function () {
    it('return to fullPath', function (string $path, string $expected) {
        $actual = TrackedPath::parse($path);

        expect($actual->full())->toBe($expected);
    })
        ->with([
            [
                'README.md',
                F_GIT_TRACKING_ROOT . '/README.md'
            ],
            [
                'src/main.go',
                F_GIT_TRACKING_ROOT . '/src/main.go'
            ],
            [
                'src/user/http/handler.go',
                F_GIT_TRACKING_ROOT . '/src/user/http/handler.go'
            ],
        ]);
});
