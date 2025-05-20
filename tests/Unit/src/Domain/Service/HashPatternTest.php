<?php

declare(strict_types=1);

use Phpgit\Domain\Service\HashPattern;

describe('parsePath', function () {
    it(
        'match to sha1',
        function (string $value) {
            $actual = HashPattern::sha1($value);

            expect($actual)->toBeTrue();
        }
    )
        ->with([
            ['8151325dcdbae9e0ff95f9f9658432dbedfdb209'],
            ['829c3804401b0727f70f73d4415e162400cbe57b'],
            ['a415ab5cc17c8c093c015ccdb7e552aee7911aa4'],
        ]);

    it(
        'unmatch to sha1',
        function (string $value) {
            $actual = HashPattern::sha1($value);

            expect($actual)->toBeFalse();
        }
    )
        ->with([
            'include char f' => ['g151325dcdbae9e0ff95f9f9658432dbedfdb209'],
            'over string length' => ['829c3804401b0727f70f73d4415e162400cbe57bd'],
        ]);
});
