<?php

declare(strict_types=1);

use Phpgit\Module\LineDiff\Differ;

describe('__invoke', function () {
    it(
        'diff',
        function (string $old, string $new, string $expected) {
            $deffer = new Differ;
            $actual = $deffer($old, $new);

            expect($actual->toUnifiedString())->toBe($expected);
        }
    )
        ->with([
            fn() => [
                'old' => file_get_contents(__DIR__ . '/storage/001_old.md'),
                'new' => file_get_contents(__DIR__ . '/storage/001_new.md'),
                'expected' => file_get_contents(__DIR__ . '/storage/001_expected.md')
            ]
        ]);

    it(
        'simple diff',
        function () {
            $deffer = new Differ;
            $actual = $deffer("A\nG\nC\nA\nT", "G\nA\nC");

            expect($actual->toUnifiedString())->toBe("- A\nG\n- C\nA\n- T\n+ C");
        }
    );
});
