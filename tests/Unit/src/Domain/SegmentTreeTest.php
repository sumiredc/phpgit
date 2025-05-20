<?php

declare(strict_types=1);

use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\SegmentTree;
use Phpgit\Domain\TrackingPath;
use Tests\Factory\FileStatFactory;
use Tests\Factory\ObjectHashFactory;

describe('new', function () {
    it('should initializes segment tree', function () {
        $tree = SegmentTree::new();

        expect($tree)->toBeInstanceOf(SegmentTree::class);
    });
});

describe('addEntry', function () {
    it('should adds entry', function (string $segmentName, IndexEntry $indexEntry) {
        $tree = SegmentTree::new();
        $tree->addEntry($segmentName, $indexEntry);

        expect($tree->getEntry($segmentName))->toEqual($indexEntry);
    })
        ->with([
            [
                'README.md',
                IndexEntry::new(
                    FileStatFactory::new(),
                    ObjectHashFactory::new(),
                    TrackingPath::new('README.md'),
                )
            ]
        ]);

    it(
        'throws the InvalidArgumentException when exists same segment',
        function (string $segmentName, IndexEntry $indexEntry, Throwable $expected) {
            $tree = SegmentTree::new();
            $tree->addEntry($segmentName, $indexEntry);

            expect(fn() => $tree->addEntry($segmentName, $indexEntry))->toThrow($expected);
        }
    )
        ->with([
            [
                'README.md',
                IndexEntry::new(
                    FileStatFactory::new(),
                    ObjectHashFactory::new(),
                    TrackingPath::new('README.md'),
                ),
                new InvalidArgumentException('already exists key: README.md')
            ]
        ]);
});

describe('addNewSegmentTree', function () {
    it('should adds segment tree of empty', function (string $segmentName) {
        $tree = SegmentTree::new();
        $actual = $tree->addNewSegmentTree($segmentName);

        expect($actual)->toEqual($tree->getSegmentTree($segmentName));
    })
        ->with([
            ['src']
        ]);

    it(
        'throws the InvalidArgumentException when exists tree by segment',
        function (string $segmentName, Throwable $expected) {
            $tree = SegmentTree::new();
            $tree->addNewSegmentTree($segmentName);

            expect(fn() => $tree->addNewSegmentTree($segmentName))->toThrow($expected);
        }
    )
        ->with([
            ['src', new InvalidArgumentException('already exists key: src')]
        ]);
});

describe('isExists', function () {
    it(
        'should returns if the entry exists, false if it does not exists',
        function (
            string $segmentName,
            IndexEntry $indexEntry,
            string $checkSegmentName,
            bool $expected
        ) {
            $tree = SegmentTree::new();
            $tree->addEntry($segmentName, $indexEntry);

            $actual = $tree->isExists($checkSegmentName);

            expect($actual)->toBe($expected);
        }
    )
        ->with([
            [
                'segmentName' => 'FEATURES.md',
                'indexEntry' => IndexEntry::new(
                    FileStatFactory::new(),
                    ObjectHashFactory::new(),
                    TrackingPath::new('docs/FEATURES.md'),
                ),
                'checkSegmentName' => 'FEATURES.md',
                'expected' => true

            ],
            [
                'segmentName' => 'main.go',
                'indexEntry' => IndexEntry::new(
                    FileStatFactory::new(),
                    ObjectHashFactory::new(),
                    TrackingPath::new('src/main.go'),
                ),
                'checkSegmentName' => 'main.rs',
                'expected' => false
            ],
        ]);

    it(
        'should returns if the segment exists, false if it does not exists',
        function (
            string $segmentName,
            string $checkSegmentName,
            bool $expected
        ) {
            $tree = SegmentTree::new();
            $tree->addNewSegmentTree($segmentName);

            $actual = $tree->isExists($checkSegmentName);

            expect($actual)->toBe($expected);
        }
    )
        ->with([
            [
                'segmentName' => 'docs',
                'checkSegmentName' => 'docs',
                'expected' => true

            ],
            [
                'segmentName' => 'src',
                'checkSegmentName' => 'app',
                'expected' => false
            ],
        ]);
});

describe('getEntry', function () {
    it(
        'should returns entry when match segment name',
        function (array $entries, string $segmentName, IndexEntry $expected) {
            $tree = SegmentTree::new();
            foreach ($entries as $k => $v) {
                $tree->addEntry($k, $v);
            }

            $actual = $tree->getEntry($segmentName);

            expect($actual)->toEqual($expected);
        }
    )
        ->with([
            [
                'entries' => [
                    'README.md' => IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHashFactory::new(),
                        TrackingPath::new('README.md'),
                    ),
                    'FEATURES.md' => IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHashFactory::new(),
                        TrackingPath::new('FEATURES.md'),
                    ),
                ],
                'segmentName' => 'README.md',
                'expected' => IndexEntry::new(
                    FileStatFactory::new(),
                    ObjectHashFactory::new(),
                    TrackingPath::new('README.md'),
                )
            ],
        ]);

    it(
        'throws BadMethodCallException when does not exists entry',
        function (array $entries, string $segmentName, Throwable $expected) {
            $tree = SegmentTree::new();
            foreach ($entries as $k => $v) {
                $tree->addEntry($k, $v);
            }

            expect(fn() => $tree->getEntry($segmentName))->toThrow($expected);
        }
    )
        ->with([
            [
                'entries' => [
                    'README.md' => IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHashFactory::new(),
                        TrackingPath::new('README.md'),
                    ),
                    'FEATURES.md' => IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHashFactory::new(),
                        TrackingPath::new('FEATURES.md'),
                    ),
                ],
                'segmentName' => 'main.go',
                'expected' => new BadMethodCallException('is not entry: main.go')
            ],
        ]);

    it(
        'throws BadMethodCallException when exists segment name but it is not entry',
        function (array $segments, string $segmentName, Throwable $expected) {
            $tree = SegmentTree::new();
            foreach ($segments as $v) {
                $tree->addNewSegmentTree($v);
            }

            expect(fn() => $tree->getEntry($segmentName))->toThrow($expected);
        }
    )
        ->with([
            [
                'segments' => [
                    'docs',
                    'src',
                ],
                'segmentName' => 'docs',
                'expected' => new BadMethodCallException('is not entry: docs')
            ],
        ]);
});

describe('getSegmentTree', function () {
    it(
        'should returns segment tree when match segment name',
        function (array $segments, string $segmentName, SegmentTree $expected) {
            $tree = SegmentTree::new();
            foreach ($segments as $k => $values) {
                $childTree = $tree->addNewSegmentTree($k);
                foreach ($values as $dir) {
                    $childTree->addNewSegmentTree($dir);
                }
            }

            $actual = $tree->getSegmentTree($segmentName);

            expect($actual)->toEqual($expected);
        }
    )
        ->with([
            [
                'segments' => [
                    'src' => ['domain', 'usecase'],
                    'docs' => ['v1', 'v2'],
                ],
                'segmentName' => 'src',
                'expected' => (function () {
                    $tree = SegmentTree::new();
                    $tree->addNewSegmentTree('usecase');
                    $tree->addNewSegmentTree('domain');

                    return $tree;
                })()
            ]
        ]);

    it(
        'throws BadMethodCallException when does exists segment tree',
        function (array $segments, string $segmentName, Throwable $expected) {
            $tree = SegmentTree::new();
            foreach ($segments as $v) {
                $tree->addNewSegmentTree($v);
            }

            expect(fn() => $tree->getSegmentTree($segmentName))->toThrow($expected);
        }
    )
        ->with([
            [
                'segments' => [
                    'docs',
                    'src',
                ],
                'segmentName' => 'app',
                'expected' => new BadMethodCallException('is not segment tree: app'),
            ]
        ]);


    it(
        'throws BadMethodCallException when exists segment name but it is not segment tree',
        function (array $entries, string $segmentName, Throwable $expected) {
            $tree = SegmentTree::new();
            foreach ($entries as $k => $v) {
                $tree->addEntry($k, $v);
            }

            expect(fn() => $tree->getSegmentTree($segmentName))->toThrow($expected);
        }
    )
        ->with([
            [
                'entries' => [
                    'README.md' => IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHashFactory::new(),
                        TrackingPath::new('README.md'),
                    ),
                    'FEATURES.md' => IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHashFactory::new(),
                        TrackingPath::new('FEATURES.md'),
                    ),
                    'docs' => IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHashFactory::new(),
                        TrackingPath::new('docs'),
                    ),
                ],
                'segmentName' => 'docs',
                'expected' => new BadMethodCallException('is not segment tree: docs'),
            ]
        ]);
});
