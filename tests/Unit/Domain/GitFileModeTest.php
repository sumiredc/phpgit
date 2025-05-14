<?php

declare(strict_types=1);

use Phpgit\Domain\GitFileMode;
use Phpgit\Domain\ObjectType;

describe('fileStatMode', function () {
    it(
        'returns to match integer',
        function (GitFileMode $mode, int $expected) {
            expect($mode->fileStatMode())->toBe($expected);
        }
    )
        ->with([
            [GitFileMode::DefaultFile, 33188],
            [GitFileMode::ExeFile, 33261],
        ]);

    it(
        'throws an exception on unsupported GitFileMode',
        function (GitFileMode $mode, Throwable $expected) {
            expect(fn() => $mode->fileStatMode())->toThrow($expected);
        }
    )
        ->with([
            [GitFileMode::Tree, new UnexpectedValueException('Unsupported GitFileMode: Tree')],
            [GitFileMode::SymbolicLink, new UnexpectedValueException('Unsupported GitFileMode: SymbolicLink')],
            [GitFileMode::SubModule, new UnexpectedValueException('Unsupported GitFileMode: SubModule')],
        ]);
});

describe('toObjectType', function () {
    it(
        'return to match the ObjectType',
        function (GitFileMode $mode, ObjectType $expected) {
            expect($mode->toObjectType())->toBe($expected);
        }
    )
        ->with([
            [GitFileMode::Tree, ObjectType::Tree],
            [GitFileMode::DefaultFile, ObjectType::Blob],
            [GitFileMode::ExeFile, ObjectType::Blob],
        ]);

    it(
        'throws an exception on unsupported GitFileMode',
        function (GitFileMode $mode, Throwable $expected) {
            expect(fn() => $mode->toObjectType())->toThrow($expected);
        }
    )
        ->with([
            [GitFileMode::SymbolicLink, new UnexpectedValueException('Unsupported GitFileMode: SymbolicLink')],
            [GitFileMode::SubModule, new UnexpectedValueException('Unsupported GitFileMode: SubModule')],
        ]);
});
