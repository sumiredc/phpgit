<?php

declare(strict_types=1);

use Phpgit\Domain\IndexObjectType;

describe('parseFlags', function () {
    it('should match to parse flags value', function (int $flags, IndexObjectType $expected) {
        $actual = IndexObjectType::parseFlags($flags);

        expect($actual)->toBe($expected);
    })
        ->with([
            [32768, IndexObjectType::Normal],
            [40960, IndexObjectType::SymbolicLink],
            [57344, IndexObjectType::GitLink],
        ]);
});

describe('asStorableValue', function () {
    it('should match to convert value', function (IndexObjectType $type, int $expected) {
        expect($type->asStorableValue())->toBe($expected);
    })
        ->with([
            [IndexObjectType::Normal, 32768],
            [IndexObjectType::SymbolicLink, 40960],
            [IndexObjectType::GitLink, 57344],
        ]);
});
