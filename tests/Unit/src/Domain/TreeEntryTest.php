<?php

declare(strict_types=1);

use Phpgit\Domain\GitFileMode;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\ObjectType;
use Phpgit\Domain\TreeEntry;
use Tests\Factory\ObjectHashFactory;

describe('new', function () {
    it(
        'matches to args to properties',
        function (
            ObjectType $objectType,
            GitFileMode $gitFileMode,
            string $objectName,
            ObjectHash $objectHash
        ) {
            $actual = TreeEntry::new($objectType, $gitFileMode, $objectName, $objectHash);

            expect($actual->objectType)->toBe($objectType);
            expect($actual->gitFileMode)->toBe($gitFileMode);
            expect($actual->objectName)->toBe($objectName);
            expect($actual->objectHash)->toBe($objectHash);
        }
    )
        ->with([
            fn() => [
                ObjectType::Blob,
                GitFileMode::DefaultFile,
                'object-blob-default',
                ObjectHash::parse('7e240de74fb1ed08fa08d38063f6a6a91462a815')
            ],
            fn() => [
                ObjectType::Blob,
                GitFileMode::ExeFile,
                'object-blob-exe',
                ObjectHash::parse('5cb138284d431abd6a053a56625ec088bfb88912')
            ],
            fn() => [
                ObjectType::Tree,
                GitFileMode::Tree,
                'object-tree',
                ObjectHash::parse('f36b4825e5db2cf7dd2d2593b3f5c24c0311d8b2')
            ],
        ]);

    it(
        'throws an exception on does not allow object type',
        function (ObjectType $objectType, Throwable $expected) {
            expect(
                fn() => TreeEntry::new(
                    $objectType,
                    GitFileMode::Unknown,
                    'dummy-commit',
                    ObjectHashFactory::new()
                )
            )
                ->toThrow($expected);
        }
    )
        ->with([
            [
                ObjectType::Commit,
                new InvalidArgumentException('not allowed ObjectType: Commit')
            ]
        ]);
});
