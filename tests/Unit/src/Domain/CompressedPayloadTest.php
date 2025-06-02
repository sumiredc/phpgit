<?php

declare(strict_types=1);

use Phpgit\Domain\CompressedPayload;

beforeEach(function () {
    set_error_handler(fn() => true);
});

afterEach(function () {
    restore_error_handler();
});

describe('new', function () {
    it('should match to property to arg', function (string $value, string $expected) {
        $actual = CompressedPayload::new($value);

        expect($actual->value)->toBe($expected);
    })
        ->with([
            [
                gzcompress('compressed string'),
                gzcompress('compressed string')
            ],
        ]);

    it(
        'throws the InvalidArgumentException when take a uncompressed string',
        function (string $value, Throwable $expected) {
            expect(fn() => CompressedPayload::new($value))->toThrow($expected);
        }
    )
        ->with([
            [
                'uncompressed string',
                new InvalidArgumentException('not uncompressed: uncompressed string')
            ],
        ]);
});

describe('fromOriginal', function () {
    it('should be compressed', function (string $original, string $expected) {
        $actual = CompressedPayload::fromOriginal($original);

        expect($actual->value)->toBe($expected);
    })
        ->with([
            ['original string', gzcompress('original string')],
        ]);
});

describe('decompress', function () {
    it('should return decompress string', function (string $original, string $expected) {
        $payload = CompressedPayload::fromOriginal($original);
        $actual = $payload->decompress();

        expect($actual)->toBe($expected);
    })
        ->with([
            ['original string', 'original string'],
        ]);
});
