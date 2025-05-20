<?php

declare(strict_types=1);

use Phpgit\Domain\FileStat;
use Phpgit\Domain\GitIndex;
use Phpgit\Domain\GitIndexHeader;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\TrackingFile;
use Tests\Factory\FileStatFactory;
use Tests\Factory\GitIndexHeaderFactory;
use Tests\Factory\ObjectHashFactory;

describe('new', function () {
    it(
        'match to args to properties',
        function (string $signature, int $version, int $count) {
            $actual = GitIndex::new();

            expect($actual->signature)->toBe($signature);
            expect($actual->version)->toBe($version);
            expect($actual->count)->toBe($count);
        }
    )
        ->with([
            [GIT_INDEX_SIGNATURE, GIT_INDEX_VERSION, 0]
        ]);
});

describe('parse', function () {
    it(
        'match to args to properties',
        function (GitIndexHeader $header) {
            $actual = GitIndex::parse($header);

            expect($actual->signature)->toBe($header->signature);;
            expect($actual->version)->toBe($header->version);
            expect($actual->count)->toBe($header->count);
        }
    )
        ->with([
            [GitIndexHeader::new()],
        ]);
});

describe('loadEntry, isLoadedEntries, assert', function () {
    it(
        'loads entry and returns current entries count',
        function (GitIndexHeader $header, array $entries) {
            $index = GitIndex::parse($header);
            foreach ($entries as $entry) {
                expect($index->isLoadedEntries())->toBeFalse();

                $index->loadEntry($entry);
            }

            expect($index->isLoadedEntries())->toBeTrue();

            $index->assert();
            expect(true)->toBeTrue();
        }
    )
        ->with([
            '3 entry' => [
                GitIndexHeaderFactory::new(3),
                [
                    IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHashFactory::new(),
                        TrackingFile::new('README.md')
                    ),
                    IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHashFactory::new(),
                        TrackingFile::new('src/main.go')
                    ),
                    IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHashFactory::new(),
                        TrackingFile::new('src/domain/user.go')
                    ),
                ]
            ],
            '5 entry' => [
                GitIndexHeaderFactory::new(5),
                [
                    IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHashFactory::new(),
                        TrackingFile::new('README.md')
                    ),
                    IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHashFactory::new(),
                        TrackingFile::new('src/main.go')
                    ),
                    IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHashFactory::new(),
                        TrackingFile::new('src/domain/user.go')
                    ),
                    IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHashFactory::new(),
                        TrackingFile::new('src/domain/detail.go')
                    ),
                    IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHashFactory::new(),
                        TrackingFile::new('src/domain/address.go')
                    ),
                ]
            ]
        ]);

    it(
        'throws an exception on entry overflow',
        function (GitIndexHeader $header, array $entries, Throwable $expected) {
            $assert = function () use ($header, $entries) {
                $index = GitIndex::parse($header);

                foreach ($entries as $entry) {
                    $index->loadEntry($entry);
                }
            };

            expect(fn() => $assert())->toThrow($expected);
        }
    )
        ->with([
            'assert 4 but expected 5 entry' => [
                GitIndexHeaderFactory::new(4),
                [
                    IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHashFactory::new(),
                        TrackingFile::new('README.md')
                    ),
                    IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHashFactory::new(),
                        TrackingFile::new('src/main.go')
                    ),
                    IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHashFactory::new(),
                        TrackingFile::new('src/domain/user.go')
                    ),
                    IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHashFactory::new(),
                        TrackingFile::new('src/domain/detail.go')
                    ),
                    IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHashFactory::new(),
                        TrackingFile::new('src/domain/address.go')
                    ),
                ],
                new OverflowException('Too many entries loaded from index file'),
            ]
        ]);

    it(
        'throws an exception on unloaded entries',
        function (GitIndexHeader $header, array $entries, Throwable $expected) {
            $index = GitIndex::parse($header);
            foreach ($entries as $entry) {
                $index->loadEntry($entry);
            }

            expect(fn() => $index->assert())->toThrow($expected);
        }
    )
        ->with([
            'assert 4 but expected 5 entry' => [
                GitIndexHeaderFactory::new(5),
                [
                    IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHashFactory::new(),
                        TrackingFile::new('README.md')
                    ),
                    IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHashFactory::new(),
                        TrackingFile::new('src/main.go')
                    ),
                    IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHashFactory::new(),
                        TrackingFile::new('src/domain/user.go')
                    ),
                    IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHashFactory::new(),
                        TrackingFile::new('src/domain/detail.go')
                    ),
                ],
                new AssertionError('Expected 5 index entries, but only 4 were loaded'),
            ]
        ]);
});


describe('addEntry', function () {
    it(
        'adds entry and returns entries count',
        function (array $entries) {
            $expectedCount = 0;
            $index = GitIndex::new();

            expect($index->count)->toBe($expectedCount);

            foreach ($entries as $entry) {
                $actual = $index->addEntry($entry);
                $expectedCount++;

                expect($actual)->toBe($expectedCount)->toBe($index->count);
            }
        }
    )
        ->with([
            [
                array_map(fn(string $filename) => IndexEntry::new(
                    FileStat::new([
                        'dev' => 16777232,
                        'ino' => 63467197,
                        'mode' => 33261,
                        'nlink' => 1,
                        'uid' => 501,
                        'gid' => 20,
                        'rdev' => 0,
                        'size' => 53,
                        'atime' => 1744383757,
                        'mtime' => 1744383756,
                        'ctime' => 1745070011,
                        'blksize' => 4096,
                        'blocks' => 8,
                    ]),
                    ObjectHash::new('8ec9a00bfd09b3190ac6b22251dbb1aa95a0579d'),
                    TrackingFile::new($filename)
                ), [
                    'README.md',
                    'CONTRIBUTING.md',
                    'src/main.go',
                    'src/domain/user.go',
                    'src/domain/product.go',
                    'src/usecase/create_user.go',
                    'src/usecase/update_user.go',
                ])
            ]
        ]);

    it(
        'should overwrite entry when add same entry',
        function (
            IndexEntry $oldEntry,
            IndexEntry $newEntry,
            string $key
        ) {
            $index = GitIndex::new();
            $index->addEntry($oldEntry);
            $index->addEntry($newEntry);

            expect($index->entries[$key])->toBe($newEntry);
            expect($index->count)->toBe(1);
        }
    )
        ->with([
            [
                'oldEntry' => IndexEntry::new(
                    FileStat::new([
                        'dev' => 16777232,
                        'ino' => 63467197,
                        'mode' => 33261,
                        'nlink' => 1,
                        'uid' => 501,
                        'gid' => 20,
                        'rdev' => 0,
                        'size' => 53,
                        'atime' => 1744383757,
                        'mtime' => 1744383756,
                        'ctime' => 1745070011,
                        'blksize' => 4096,
                        'blocks' => 8,
                    ]),
                    ObjectHash::new('8ec9a00bfd09b3190ac6b22251dbb1aa95a0579d'),
                    TrackingFile::new('README.md')
                ),
                'newEntry' => IndexEntry::new(
                    FileStat::new([
                        'dev' => 16777210,
                        'ino' => 63467110,
                        'mode' => 33188,
                        'nlink' => 2,
                        'uid' => 500,
                        'gid' => 18,
                        'rdev' => 3,
                        'size' => 56,
                        'atime' => 1744383799,
                        'mtime' => 1744383788,
                        'ctime' => 1745070077,
                        'blksize' => 57,
                        'blocks' => 10,
                    ]),
                    ObjectHash::new('10a590f971f9b0fe8ae0e11155865cba046d1ae1'),
                    TrackingFile::new('README.md')
                ),
                'key' => 'README.md'
            ]
        ]);

    it(
        'should sorted by key entries',
        function (array $entries, array $order) {
            $expected = 0;
            $index = GitIndex::new();

            foreach ($entries as $entry) {
                $index->addEntry($entry);
            }

            foreach ($index->entries as $filename => $entry) {
                expect($filename)->toBe($order[$expected]);
                $expected++;
            }
        }
    )
        ->with([
            [
                'entries' => array_map(fn(string $filename) => IndexEntry::new(
                    FileStat::new([
                        'dev' => 16777232,
                        'ino' => 63467197,
                        'mode' => 33261,
                        'nlink' => 1,
                        'uid' => 501,
                        'gid' => 20,
                        'rdev' => 0,
                        'size' => 53,
                        'atime' => 1744383757,
                        'mtime' => 1744383756,
                        'ctime' => 1745070011,
                        'blksize' => 4096,
                        'blocks' => 8,
                    ]),
                    ObjectHash::new('8ec9a00bfd09b3190ac6b22251dbb1aa95a0579d'),
                    TrackingFile::new($filename)
                ), [
                    'src/usecase/update_user.go',
                    'CONTRIBUTING.md',
                    'src/usecase/create_user.go',
                    'src/domain/product.go',
                    'src/main.go',
                    'README.md',
                    'src/domain/user.go',
                ]),
                'order' => [
                    0 => 'CONTRIBUTING.md',
                    1 => 'README.md',
                    2 => 'src/domain/product.go',
                    3 => 'src/domain/user.go',
                    4 => 'src/main.go',
                    5 => 'src/usecase/create_user.go',
                    6 => 'src/usecase/update_user.go',
                ]
            ]
        ]);
});

describe('entriesBlob', function () {
    it(
        'should match to entries blob',
        function (array $entries, string $expected) {
            $index = GitIndex::new();
            foreach ($entries as $entry) {
                $index->addEntry($entry);
            }

            expect($index->entriesBlob())->toBe($expected);
        }
    )
        ->with([
            function () {
                $entries = array_map(fn(string $filename) => IndexEntry::new(
                    FileStat::new([
                        'dev' => 16777232,
                        'ino' => 63467197,
                        'mode' => 33261,
                        'nlink' => 1,
                        'uid' => 501,
                        'gid' => 20,
                        'rdev' => 0,
                        'size' => 53,
                        'atime' => 1744383757,
                        'mtime' => 1744383756,
                        'ctime' => 1745070011,
                        'blksize' => 4096,
                        'blocks' => 8,
                    ]),
                    ObjectHash::new('8ec9a00bfd09b3190ac6b22251dbb1aa95a0579d'),
                    TrackingFile::new($filename)
                ), [
                    'CONTRIBUTING.md',
                    'README.md',
                    'src/domain/product.go',
                    'src/domain/user.go',
                    'src/main.go',
                    'src/usecase/create_user.go',
                    'src/usecase/update_user.go',
                ]);

                $expected = '';
                foreach ($entries as $entry) {
                    $expected .= $entry->asBlob();
                }

                return compact('entries', 'expected');
            }
        ]);
});

describe('asBlob', function () {
    it(
        'should match to blob',
        function (array $entries, string $expected) {
            $index = GitIndex::new();
            foreach ($entries as $entry) {
                $index->addEntry($entry);
            }

            expect($index->asBlob())->toBe($expected);
        }
    )
        ->with([
            function () {
                $entries = array_map(fn(string $filename) => IndexEntry::new(
                    FileStat::new([
                        'dev' => 16777232,
                        'ino' => 63467197,
                        'mode' => 33261,
                        'nlink' => 1,
                        'uid' => 501,
                        'gid' => 20,
                        'rdev' => 0,
                        'size' => 53,
                        'atime' => 1744383757,
                        'mtime' => 1744383756,
                        'ctime' => 1745070011,
                        'blksize' => 4096,
                        'blocks' => 8,
                    ]),
                    ObjectHash::new('8ec9a00bfd09b3190ac6b22251dbb1aa95a0579d'),
                    TrackingFile::new($filename)
                ), [
                    'CONTRIBUTING.md',
                    'README.md',
                    'src/domain/product.go',
                    'src/domain/user.go',
                    'src/main.go',
                    'src/usecase/create_user.go',
                    'src/usecase/update_user.go',
                ]);

                $blob = pack('a4NN', GIT_INDEX_SIGNATURE, GIT_INDEX_VERSION, 7);
                foreach ($entries as $entry) {
                    $blob .= $entry->asBlob();
                }

                $expected = $blob . hash('sha1', $blob, true);

                return compact('entries', 'expected');
            }
        ]);
});

describe('existsEntry', function () {
    it(
        'should match results to exists entry',
        function (string $path, bool $expected) {
            $entries = array_map(fn(string $filename) => IndexEntry::new(
                FileStat::new([
                    'dev' => 16777232,
                    'ino' => 63467197,
                    'mode' => 33261,
                    'nlink' => 1,
                    'uid' => 501,
                    'gid' => 20,
                    'rdev' => 0,
                    'size' => 53,
                    'atime' => 1744383757,
                    'mtime' => 1744383756,
                    'ctime' => 1745070011,
                    'blksize' => 4096,
                    'blocks' => 8,
                ]),
                ObjectHash::new('8ec9a00bfd09b3190ac6b22251dbb1aa95a0579d'),
                TrackingFile::new($filename)
            ), [
                'CONTRIBUTING.md',
                'README.md',
                'src/domain/product.go',
                'src/domain/user.go',
                'src/main.go',
                'src/usecase/create_user.go',
                'src/usecase/update_user.go',
            ]);

            $index = GitIndex::new();
            foreach ($entries as $entry) {
                $index->addEntry($entry);
            }

            expect($index->existsEntry(TrackingFile::new($path)))->toBe($expected);
        }
    )
        ->with([
            ['CONTRIBUTING.md', true],
            ['README.md', true],
            ['src/domain/product.go', true],
            ['src/domain/user.go', true],
            ['src/main.go', true],
            ['src/usecase/create_user.go', true],
            ['src/usecase/update_user.go', true],
            ['src/main.rs', false],
            ['src/domain/employee.go', false],
            ['README.test', false],
        ]);
});

describe('existsEntryByFilename', function () {
    it(
        'should match results to exists entry by path',
        function (string $path, bool $expected) {
            $entries = array_map(fn(string $filename) => IndexEntry::new(
                FileStat::new([
                    'dev' => 16777232,
                    'ino' => 63467197,
                    'mode' => 33261,
                    'nlink' => 1,
                    'uid' => 501,
                    'gid' => 20,
                    'rdev' => 0,
                    'size' => 53,
                    'atime' => 1744383757,
                    'mtime' => 1744383756,
                    'ctime' => 1745070011,
                    'blksize' => 4096,
                    'blocks' => 8,
                ]),
                ObjectHash::new('8ec9a00bfd09b3190ac6b22251dbb1aa95a0579d'),
                TrackingFile::new($filename)
            ), [
                'CONTRIBUTING.md',
                'README.md',
                'src/domain/product.go',
                'src/domain/user.go',
                'src/main.go',
                'src/usecase/create_user.go',
                'src/usecase/update_user.go',
            ]);

            $index = GitIndex::new();
            foreach ($entries as $entry) {
                $index->addEntry($entry);
            }

            expect($index->existsEntryByFilename($path))->toBe($expected);
        }
    )
        ->with([
            ['CONTRIBUTING.md', true],
            ['README.md', true],
            ['src/domain/product.go', true],
            ['src/domain/user.go', true],
            ['src/main.go', true],
            ['src/usecase/create_user.go', true],
            ['src/usecase/update_user.go', true],
            ['src/main.rs', false],
            ['src/domain/employee.go', false],
            ['README.test', false],
        ]);
});

describe('removeEntryByFilename', function () {
    it(
        'should removes entry',
        function (string $path) {
            $entries = array_map(fn(string $filename) => IndexEntry::new(
                FileStat::new([
                    'dev' => 16777232,
                    'ino' => 63467197,
                    'mode' => 33261,
                    'nlink' => 1,
                    'uid' => 501,
                    'gid' => 20,
                    'rdev' => 0,
                    'size' => 53,
                    'atime' => 1744383757,
                    'mtime' => 1744383756,
                    'ctime' => 1745070011,
                    'blksize' => 4096,
                    'blocks' => 8,
                ]),
                ObjectHash::new('8ec9a00bfd09b3190ac6b22251dbb1aa95a0579d'),
                TrackingFile::new($filename)
            ), [
                'CONTRIBUTING.md',
                'README.md',
                'src/domain/product.go',
                'src/domain/user.go',
                'src/main.go',
                'src/usecase/create_user.go',
                'src/usecase/update_user.go',
            ]);

            $index = GitIndex::new();
            foreach ($entries as $entry) {
                $index->addEntry($entry);
            }

            $index->removeEntryByFilename($path);

            expect($index->existsEntryByFilename($path))->toBe(false);
            expect($index->existsEntry(TrackingFile::new($path)))->toBe(false);
        }
    )
        ->with([
            ['CONTRIBUTING.md'],
            ['README.md'],
            ['src/domain/product.go'],
            ['src/domain/user.go'],
            ['src/main.go'],
            ['src/usecase/create_user.go'],
            ['src/usecase/update_user.go'],
            // no error occurs when don't exists entry
            ['src/main.rs'],
            ['src/domain/employee.go'],
            ['README.test'],
        ]);
});
