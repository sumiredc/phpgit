<?php

declare(strict_types=1);

use Phpgit\Domain\GitFileMode;

describe('fileStatMode', function () {
    it('should return to file stat mode', function (GitFileMode $mode, int $expected) {
        expect($mode->fileStatMode())->toBe($expected);
    })
        ->with([
            [GitFileMode::DefaultFile, 33188],
            [GitFileMode::ExeFile, 33261],
        ]);

    it('fails value', function (GitFileMode $mode) {
        $mode->fileStatMode();
    })
        ->with([
            [GitFileMode::Tree],
            [GitFileMode::SymbolicLink],
            [GitFileMode::SubModule],
        ])
        ->throws(ValueError::class);
});
