<?php

declare(strict_types=1);

use Phpgit\Domain\UnixPermission;

describe('parseFlags', function () {
    it(
        'returns to match UnixPermission',
        function (int $dec, UnixPermission $expected) {
            $actual = UnixPermission::parseFlags($dec);

            expect($actual)->toBe($expected);
        }
    )
        ->with([
            [0, UnixPermission::Zero],
            [493, UnixPermission::RwxRxRx],
            [420, UnixPermission::RwRR],
        ]);

    it(
        'fails ValueError',
        function (int $dec, Throwable $expected) {
            expect(fn() => UnixPermission::parseFlags($dec))->toThrow($expected);
        }
    )
        ->with([
            [1, new ValueError('value error: 1')],
            [450, new ValueError('value error: 450')],
            [644, new ValueError('value error: 644')],
            [755, new ValueError('value error: 755')],
        ]);
});

describe('fromStatMode', function () {
    it(
        'returns to match UnixPermission',
        function (int $mode, UnixPermission $expected) {
            $actual = UnixPermission::fromStatMode($mode);

            expect($actual)->toBe($expected);
        }
    )
        ->with([
            [33152, UnixPermission::RwRR],
            [33188, UnixPermission::RwRR],
            [33204, UnixPermission::RwRR],
            [33216, UnixPermission::RwxRxRx],
            [33261, UnixPermission::RwxRxRx],
            [33272, UnixPermission::RwxRxRx],
        ]);
});
