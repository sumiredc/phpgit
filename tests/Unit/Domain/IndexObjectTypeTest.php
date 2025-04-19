<?php

declare(strict_types=1);

use Phpgit\Domain\IndexObjectType;

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
