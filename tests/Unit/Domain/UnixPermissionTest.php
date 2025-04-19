<?php

declare(strict_types=1);

use Phpgit\Domain\UnixPermission;

describe('fromDec', function () {
    it('returns to match UnixPermission', function (int $dec, UnixPermission $expected) {
        $actual = UnixPermission::fromDec($dec);

        expect($actual)->toBe($expected);
    })
        ->with([
            [0, UnixPermission::Zero],
            [493, UnixPermission::RwxRxRx],
            [420, UnixPermission::RwRR],
        ]);

    it('fails ValueError', function (int $dec) {
        UnixPermission::fromDec($dec);
    })
        ->with([
            [1],
            [450],
            [644],
            [755],
        ])
        ->throws(ValueError::class);
});

describe('fromStatMode', function () {
    it('returns to match UnixPermission', function (int $mode, UnixPermission $expected) {
        $actual = UnixPermission::fromStatMode($mode);

        expect($actual)->toBe($expected);
    })
        ->with([
            [33152, UnixPermission::RwRR],
            [33188, UnixPermission::RwRR],
            [33204, UnixPermission::RwRR],
            [33216, UnixPermission::RwxRxRx],
            [33261, UnixPermission::RwxRxRx],
            [33272, UnixPermission::RwxRxRx],
        ]);
});
