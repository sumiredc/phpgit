<?php

declare(strict_types=1);

use Phpgit\Domain\GitFileMode;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\ObjectType;
use Phpgit\Domain\TreeObject;

describe('new', function () {
    it('should initialize', function () {
        $actual = TreeObject::new();

        expect($actual->objectType)->toBe(ObjectType::Tree);
        expect($actual->size)->toBe(0);
        expect($actual->body)->toBe('');
    });
});

describe('appendEntry', function () {
    it('should match body', function (array $entry1, array $entry2, string $expected) {
        $tree = TreeObject::new();
        foreach ([$entry1, $entry2] as [$mode, $type, $hash, $name]) {
            $tree->appendEntry($mode, $type, $hash, $name);
        }

        expect($tree->body)->toBe($expected);
    })
        ->with([
            [
                [
                    GitFileMode::DefaultFile,
                    ObjectType::Blob,
                    ObjectHash::parse('34a2d4555e37ca2ad68563f0ce17d327b8bc0301'),
                    'README.md'
                ],
                [
                    GitFileMode::Tree,
                    ObjectType::Tree,
                    ObjectHash::parse('5dee59773f75e23b248965ccb9c5dbeebe875093'),
                    'src'
                ],
                '100644 blob 34a2d4555e37ca2ad68563f0ce17d327b8bc0301	README.md
040000 tree 5dee59773f75e23b248965ccb9c5dbeebe875093	src
'
            ]
        ]);
});
