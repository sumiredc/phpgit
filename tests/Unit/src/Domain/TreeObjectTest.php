<?php

declare(strict_types=1);

use Phpgit\Domain\GitFileMode;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\ObjectType;
use Phpgit\Domain\TreeEntry;
use Phpgit\Domain\TreeObject;
use Tests\Factory\ObjectHashFactory;

describe('new', function () {
    it(
        'should initialize',
        function () {
            $actual = TreeObject::new();

            expect($actual->objectType)->toBe(ObjectType::Tree);
            expect($actual->size)->toBe(0);
            expect($actual->body)->toBe('');
            expect($actual->data)->toBe("tree 0\0");
        }
    );
});

describe('parse', function () {
    it('fails to parse', function (string $blob, Throwable $expected) {
        expect(fn() => TreeObject::parse($blob))->toThrow($expected);
    })
        ->with([
            [
                "blob 73\0" . 'package main

import "fmt"

func main() {
	fmt.Println("Hello, world")
}
',
                new UnexpectedValueException('unexpected ObjectType value: blob')
            ]
        ]);
});

describe('appendEntry', function () {
    it(
        'should match body',
        function (array $entries, string $expected) {
            $tree = TreeObject::new();
            foreach ($entries as [$mode, $hash, $name]) {
                $tree->appendEntry($mode, $hash, $name);
            }

            expect($tree->body)->toBe($expected);
        }
    )
        ->with([
            [
                [
                    [
                        GitFileMode::DefaultFile,
                        ObjectHash::parse('34a2d4555e37ca2ad68563f0ce17d327b8bc0301'),
                        'README.md'
                    ],
                    [
                        GitFileMode::Tree,
                        ObjectHash::parse('5dee59773f75e23b248965ccb9c5dbeebe875093'),
                        'src'
                    ]
                ],
                "100644 README.md\0" . hex2bin('34a2d4555e37ca2ad68563f0ce17d327b8bc0301')
                    . "40000 src\0" . hex2bin('5dee59773f75e23b248965ccb9c5dbeebe875093')
            ]
        ]);
});

describe('entries', function () {
    it(
        'matches to entries to append entries',
        function () {
            $entries = [
                TreeEntry::new(ObjectType::Blob, GitFileMode::DefaultFile, 'blob-default', ObjectHashFactory::new()),
                TreeEntry::new(ObjectType::Blob, GitFileMode::ExeFile, 'blob-exe', ObjectHashFactory::new()),
                TreeEntry::new(ObjectType::Tree, GitFileMode::Tree, 'tree', ObjectHashFactory::new()),
            ];

            $tree = TreeObject::new();
            foreach ($entries as $entry) {
                $tree->appendEntry($entry->gitFileMode, $entry->objectHash, $entry->objectName);
            }

            $actual = $tree->entries();
            foreach ($entries as $expected) {
                expect($actual->get($expected->objectName))->toEqual($expected);
            }

            expect(count($actual))->toBe(count($entries));
        }
    );
});

describe('prettyPrint', function () {
    it(
        'returns data pretty print',
        function (array $entries, string $expected) {
            $tree = TreeObject::new();
            foreach ($entries as [$mode, $hash, $name]) {
                $tree->appendEntry($mode, $hash, $name);
            }

            expect($tree->prettyPrint())->toBe($expected);
        }
    )
        ->with([
            [
                [
                    [
                        GitFileMode::DefaultFile,
                        ObjectHash::parse('34a2d4555e37ca2ad68563f0ce17d327b8bc0301'),
                        'README.md'
                    ],
                    [
                        GitFileMode::Tree,
                        ObjectHash::parse('5dee59773f75e23b248965ccb9c5dbeebe875093'),
                        'src'
                    ]
                ],
                "100644 blob 34a2d4555e37ca2ad68563f0ce17d327b8bc0301\tREADME.md\n"
                    . "040000 tree 5dee59773f75e23b248965ccb9c5dbeebe875093\tsrc\n"
            ]
        ]);
});
