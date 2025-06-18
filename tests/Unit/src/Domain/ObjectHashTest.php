<?php

declare(strict_types=1);

use Phpgit\Domain\ObjectHash;

describe('new', function () {
    it(
        'should match to hash',
        function (string $content, string $dir, string $filename) {
            $actual = ObjectHash::new($content);

            expect($actual->value)->toBe($dir . $filename);
            expect($actual->dir)->toBe($dir);
            expect($actual->filename)->toBe($filename);
        }
    )
        ->with([
            ['blob object', '81', '5675cd53e5196255182a0fd392e03df0fcd193'],
            ['tree object', '04', 'ba9ed331f1eaa7618aefb1db4da5988463404d'],
        ]);
});

describe('parse', function () {
    it(
        'should match to hash',
        function (
            string $hash,
            string $dir,
            string $filename,
            string $short
        ) {
            $actual = ObjectHash::parse($hash);

            expect($actual->value)->toBe($dir . $filename);
            expect($actual->dir)->toBe($dir);
            expect($actual->filename)->toBe($filename);
            expect($actual->short())->toBe($short);
        }
    )
        ->with([
            [
                '815675cd53e5196255182a0fd392e03df0fcd193',
                '81',
                '5675cd53e5196255182a0fd392e03df0fcd193',
                '815675c',
            ],
            [
                '04ba9ed331f1eaa7618aefb1db4da5988463404d',
                '04',
                'ba9ed331f1eaa7618aefb1db4da5988463404d',
                '04ba9ed',
            ],
        ]);

    it(
        'fails to parse',
        function (string $hash, Throwable $expected) {
            expect(fn() => ObjectHash::parse($hash))->toThrow($expected);
        }
    )
        ->with([
            [
                'not-hash',
                new InvalidArgumentException('invalid argument: not-hash')
            ],
            [
                '815675cd53e5196255182a0fd392e03df0fcd19q',
                new InvalidArgumentException('invalid argument: 815675cd53e5196255182a0fd392e03df0fcd19q')
            ]
        ]);
});

describe('zero', function () {
    it('initializes zero object', function () {
        $actual = ObjectHash::zero();

        expect($actual->value)->toBe('0000000000000000000000000000000000000000');
        expect($actual->isZero())->toBeTrue();
        expect($actual->short())->toBe('0000000');
    });
});

describe('path', function () {
    it(
        'return to path',
        function (string $hash, string $path) {
            $actual = ObjectHash::parse($hash);

            expect($actual->path())->toBe($path);
        }
    )
        ->with([
            ['815675cd53e5196255182a0fd392e03df0fcd193', '81/5675cd53e5196255182a0fd392e03df0fcd193'],
            ['04ba9ed331f1eaa7618aefb1db4da5988463404d', '04/ba9ed331f1eaa7618aefb1db4da5988463404d'],
        ]);
});


describe('fullPath', function () {
    it(
        'return to fullPath',
        function (string $hash, string $path) {
            $actual = ObjectHash::parse($hash);

            expect($actual->fullPath())->toBe($path);
        }
    )
        ->with([
            ['815675cd53e5196255182a0fd392e03df0fcd193', F_GIT_OBJECTS_DIR . '/81/5675cd53e5196255182a0fd392e03df0fcd193'],
            ['04ba9ed331f1eaa7618aefb1db4da5988463404d', F_GIT_OBJECTS_DIR . '/04/ba9ed331f1eaa7618aefb1db4da5988463404d'],
        ]);
});
