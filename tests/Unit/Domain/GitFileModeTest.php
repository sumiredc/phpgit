<?php

declare(strict_types=1);

use Phpgit\Domain\GitFileMode;

describe('fileStatMode', function () {
    it(
        'should return to file stat mode',
        function (GitFileMode $mode, int $expected) {
            expect($mode->fileStatMode())->toBe($expected);
        }
    )
        ->with([
            [GitFileMode::DefaultFile, 33188],
            [GitFileMode::ExeFile, 33261],
        ]);

    it(
        'fails value',
        function (GitFileMode $mode, Throwable $expected) {
            expect(fn() => $mode->fileStatMode())->toThrow($expected);
        }
    )
        ->with([
            [GitFileMode::Tree, new ValueError('don\'t convert to stat mode: 040000')],
            [GitFileMode::SymbolicLink, new ValueError('don\'t convert to stat mode: 120000')],
            [GitFileMode::SubModule, new ValueError('don\'t convert to stat mode: 160000')],
        ]);
});
