<?php

declare(strict_types=1);

use Phpgit\Domain\Timestamp;

describe('new', function () {
    it(
        'should match to offset',
        function (string $timezone, string $expected) {
            date_default_timezone_set($timezone);

            $actual = Timestamp::new();

            expect($actual->offset)->toBe($expected);
        }
    )
        ->with([
            ['UTC', '+0000'],
            ['Asia/Tokyo', '+0900'],
        ]);
});

describe('parse', function () {
    it(
        'should match to args to properties',
        function (int $timestamp, string $offset) {
            $actual = Timestamp::parse($timestamp, $offset);

            expect($actual->value)->toBe($timestamp);
            expect($actual->offset)->toBe($offset);
        }
    )
        ->with([
            [1746331640, '+0000'],
            [1746331688, '+0900'],
            [1746331713, '-0530'],
        ]);

    it(
        'throws exception when invalid args',
        function (int $timestamp, string $offset, Throwable $expected) {
            expect(fn() => Timestamp::parse($timestamp, $offset))->toThrow($expected);
        }
    )
        ->with([
            'invalid timestamp' => [
                -1746331640,
                '+0000',
                new UnderflowException('the lower bound for timestamps is 0: -1746331640')
            ],
            'invalid offset' => [
                1746331688,
                '0900',
                new InvalidArgumentException('not offset pattern: 0900')
            ],
        ]);
});

describe('toRawString', function () {
    it(
        'should match to raw string',
        function (int $timestamp, string $offset, string $expected) {
            $actual = Timestamp::parse($timestamp, $offset);

            expect($actual->toRawString())->toBe($expected);
        }
    )
        ->with([
            [1746331640, '+0000', '1746331640 +0000'],
            [1746331688, '+0900', '1746331688 +0900'],
            [1746331713, '-0530', '1746331713 -0530'],
        ]);
});
