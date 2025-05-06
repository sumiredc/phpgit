<?php

declare(strict_types=1);

use Phpgit\Domain\Reference;
use Phpgit\Domain\ReferenceType;

describe('new', function () {
    it(
        'match to args to properties',
        function (
            ReferenceType $refType,
            string $name,
            string $expectedPath,
            string $expectedFullPath
        ) {
            $actual = Reference::new($refType, $name);

            expect($actual->name)->toBe($name);
            expect($actual->path)->toBe($expectedPath);
            expect($actual->fullPath)->toBe($expectedFullPath);
        }
    )
        ->with([
            'local' => [
                ReferenceType::Local,
                'a',
                'refs/heads/a',
                F_GIT_DIR . '/refs/heads/a'
            ],
            'remote' => [
                ReferenceType::Remote,
                'branch/mmm',
                'refs/remotes/branch/mmm',
                F_GIT_DIR . '/refs/remotes/branch/mmm'
            ],
            'tag' => [
                ReferenceType::Tag,
                'v1.0.0',
                'refs/tags/v1.0.0',
                F_GIT_DIR . '/refs/tags/v1.0.0'
            ],
            'note' => [
                ReferenceType::Note,
                'nnn',
                'refs/notes/nnn',
                F_GIT_DIR . '/refs/notes/nnn'
            ],
            'stash' => [
                ReferenceType::Stash,
                'sss',
                'refs/stash/sss',
                F_GIT_DIR . '/refs/stash/sss'
            ],
            'replace' => [
                ReferenceType::Replace,
                'rrr',
                'refs/replace/rrr',
                F_GIT_DIR . '/refs/replace/rrr'
            ],
            'bisect' => [
                ReferenceType::Bisect,
                'bbb',
                'refs/bisect/bbb',
                F_GIT_DIR . '/refs/bisect/bbb'
            ],
        ]);
});

describe('parse', function () {
    it(
        'parse to name and type from ref',
        function (
            string $ref,
            ReferenceType $expectedRefType,
            string $expectedName
        ) {
            $actual = Reference::parse($ref);

            expect($actual->refType)->toBe($expectedRefType);
            expect($actual->name)->toBe($expectedName);
        }
    )
        ->with([
            ['refs/heads/lll', ReferenceType::Local, 'lll'],
            ['refs/remotes/rr/mm', ReferenceType::Remote, 'rr/mm'],
            ['refs/tags/v1.0.0', ReferenceType::Tag, 'v1.0.0'],
            ['refs/notes/nnn', ReferenceType::Note, 'nnn'],
            ['refs/stash/sss', ReferenceType::Stash, 'sss'],
            ['refs/replace/rrrrr', ReferenceType::Replace, 'rrrrr'],
            ['refs/bisect/bbb', ReferenceType::Bisect, 'bbb'],
        ]);

    it(
        'throws an exception on not match to reference',
        function (string $ref, Throwable $expected) {
            expect(fn() => Reference::parse($ref))->toThrow($expected);
        }
    )
        ->with([
            ['ref/heads/aaa', new InvalidArgumentException('failed to parse reference: ref/heads/aaa')],
            ['refs/remote/bbb', new InvalidArgumentException('failed to parse reference: refs/remote/bbb')],
            ['refstags/v1.0.0', new InvalidArgumentException('failed to parse reference: refstags/v1.0.0')],
            ['/refs/notes/ccc', new InvalidArgumentException('failed to parse reference: /refs/notes/ccc')],
        ]);
});
