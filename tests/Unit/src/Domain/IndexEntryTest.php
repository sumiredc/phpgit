<?php

declare(strict_types=1);

use Phpgit\Domain\FileStat;
use Phpgit\Domain\GitFileMode;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\IndexEntryHeader;
use Phpgit\Domain\IndexObjectType;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\TrackingPath;
use Phpgit\Domain\UnixPermission;

describe('new', function () {
    it('should initializes to match args to properties', function (
        FileStat $stat,
        ObjectHash $hash,
        TrackingPath $file,
        UnixPermission $expectedUnixPermission,
        GitFileMode $gitFileMode,
    ) {
        $actual = IndexEntry::new($stat, $hash, $file);

        expect($actual->ctime)->toBe($stat->ctime);
        expect($actual->ctimeNano)->toBe(0);
        expect($actual->mtime)->toBe($stat->mtime);
        expect($actual->mtimeNano)->toBe(0);
        expect($actual->dev)->toBe($stat->dev);
        expect($actual->ino)->toBe($stat->ino);
        expect($actual->mode)->toBe($stat->mode);
        expect($actual->indexObjectType)->toBe(IndexObjectType::Normal);
        expect($actual->unixPermission)->toBe($expectedUnixPermission);
        expect($actual->uid)->toBe($stat->uid);
        expect($actual->gid)->toBe($stat->gid);
        expect($actual->size)->toBe($stat->size);
        expect($actual->objectHash)->toBe($hash);
        expect($actual->trackingPath)->toBe($file);
        expect($actual->assumeValidFlag)->toBe(0);
        expect($actual->extendedFlag)->toBe(0);
        expect($actual->stage)->toBe(0);
        expect($actual->gitFileMode)->toBe($gitFileMode);
    })
        ->with([
            [
                FileStat::new([
                    'dev' => 16777232,
                    'ino' => 63058704,
                    'mode' => 33188,
                    'nlink' => 1,
                    'uid' => 501,
                    'gid' => 20,
                    'rdev' => 0,
                    'size' => 52,
                    'atime' => 1744515851,
                    'mtime' => 1744515852,
                    'ctime' => 1744515853,
                    'blksize' => 4096,
                    'blocks' => 8,
                ]),
                ObjectHash::parse('243182b9d0b085c06005bf773212854bf7cd4694'),
                TrackingPath::new('README.md'),
                UnixPermission::RwRR,
                GitFileMode::DefaultFile,
            ],
            [
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
                ObjectHash::parse('3f454a98e586d1aa0d322e19afd5e67e08f2d3c8'),
                TrackingPath::new('CONTRIBUTING.md'),
                UnixPermission::RwxRxRx,
                GitFileMode::ExeFile,
            ]
        ]);
});

describe('parse', function () {
    it('should to parse entry', function (
        string $blob,
        string $path,
        int $ctime,
        int $ctimeNano,
        int $mtime,
        int $mtimeNano,
        int $dev,
        int $ino,
        int $mode,
        IndexObjectType $indexObjectType,
        UnixPermission $unixPermission,
        int $uid,
        int $gid,
        int $size,
        string $hash,
        int $assumeValidFlag,
        int $extendedFlag,
        int $stage,
    ) {
        $header = IndexEntryHeader::parse($blob);
        $actual = IndexEntry::parse($header, $path);

        expect($actual->ctime)->toBe($ctime);
        expect($actual->ctimeNano)->toBe($ctimeNano);
        expect($actual->mtime)->toBe($mtime);
        expect($actual->mtimeNano)->toBe($mtimeNano);
        expect($actual->dev)->toBe($dev);
        expect($actual->ino)->toBe($ino);
        expect($actual->mode)->toBe($mode);
        expect($actual->indexObjectType)->toBe($indexObjectType);
        expect($actual->unixPermission)->toBe($unixPermission);
        expect($actual->uid)->toBe($uid);
        expect($actual->gid)->toBe($gid);
        expect($actual->size)->toBe($size);
        expect($actual->objectHash->value)->toBe($hash);
        expect($actual->trackingPath->value)->toBe($path);
        expect($actual->assumeValidFlag)->toBe($assumeValidFlag);
        expect($actual->extendedFlag)->toBe($extendedFlag);
        expect($actual->stage)->toBe($stage);
    })
        ->with([
            [
                // ctime, ctimeNano, mtime, mtimeNano, dev, ino, mode
                'blob' => pack('N*', 1744515851, 123, 1744515852, 456, 16777232, 63058704, 33188)
                    // uid, gid, size
                    . pack('N*', 501, 20, 52)
                    // objectName
                    . pack('H40', '8ec9a00bfd09b3190ac6b22251dbb1aa95a0579d')
                    // flags
                    . pack('n', (0 << 15) | (0 << 14) | (0 << 12) | 9),
                'path' => 'README.md',
                'ctime' => 1744515851,
                'ctimeNano' => 123,
                'mtime' => 1744515852,
                'mtimeNano' => 456,
                'dev' => 16777232,
                'ino' => 63058704,
                'mode' => 33188,
                'indexObjectType' => IndexObjectType::Normal,
                'unixPermission' => UnixPermission::RwRR,
                'uid' => 501,
                'gid' => 20,
                'size' => 52,
                'hash' => '8ec9a00bfd09b3190ac6b22251dbb1aa95a0579d',
                'assumeValidFlag' => 0,
                'extendedFlag' => 0,
                'stage' => 0,
            ],
        ]);
});

describe('asBlob', function () {
    it('match to new IndexEntry blob', function (
        FileStat $fileStat,
        ObjectHash $objectHash,
        TrackingPath $trackingPath,
        string $blob
    ) {
        $entry = IndexEntry::new($fileStat, $objectHash, $trackingPath);

        expect($entry->asBlob())->toBe($blob);
    })
        ->with([
            [
                FileStat::new([
                    'dev' => 16777232,
                    'ino' => 63058704,
                    'mode' => 33188,
                    'nlink' => 1,
                    'uid' => 501,
                    'gid' => 20,
                    'rdev' => 0,
                    'size' => 52,
                    'atime' => 1744515851,
                    'mtime' => 1744515852,
                    'ctime' => 1744515853,
                    'blksize' => 4096,
                    'blocks' => 8,
                ]),
                ObjectHash::parse('243182b9d0b085c06005bf773212854bf7cd4694'),
                TrackingPath::new('README.md'),
                // ctime, ctimeNano, mtime, mtimeNano, dev, ino, mode
                pack('N*', 1744515853, 0, 1744515852, 0, 16777232, 63058704, 33188)
                    // uid, gid, size
                    . pack('N*', 501, 20, 52)
                    // objectName
                    . pack('H40', '243182b9d0b085c06005bf773212854bf7cd4694')
                    // flags
                    . pack('n', (0 << 15) | (0 << 14) | (0 << 12) | 9)
                    . "README.md\0"
            ],
            [
                FileStat::new([
                    'dev' => 16777233,
                    'ino' => 63467197,
                    'mode' => 33261,
                    'nlink' => 1,
                    'uid' => 502,
                    'gid' => 21,
                    'rdev' => 0,
                    'size' => 53,
                    'atime' => 1744383757,
                    'mtime' => 1744383756,
                    'ctime' => 1745070011,
                    'blksize' => 4096,
                    'blocks' => 8,
                ]),
                ObjectHash::parse('8ec9a00bfd09b3190ac6b22251dbb1aa95a0579d'),
                TrackingPath::new('src/main.go'),
                // ctime, ctimeNano, mtime, mtimeNano, dev, ino, mode
                pack('N*', 1745070011, 0, 1744383756, 0, 16777233, 63467197, 33261)
                    // uid, gid, size
                    . pack('N*', 502, 21, 53)
                    // objectName
                    . pack('H40', '8ec9a00bfd09b3190ac6b22251dbb1aa95a0579d')
                    // flags
                    . pack('n', (0 << 15) | (0 << 14) | (0 << 12) | 11)
                    . "src/main.go\0"
                    . "\0\0\0\0\0\0"
            ]
        ]);

    it('match to parse IndexEntry blob', function (string $blob, string $path, string $expected) {
        $header = IndexEntryHeader::parse($blob);
        $entry = IndexEntry::parse($header, $path);

        expect($entry->asBlob())->toBe($expected);
    })
        ->with([
            [
                // ctime, ctimeNano, mtime, mtimeNano, dev, ino, mode
                'blob' => pack('N*', 1744515851, 123, 1744515852, 456, 16777232, 63058704, 33188)
                    // uid, gid, size
                    . pack('N*', 501, 20, 52)
                    // objectName
                    . pack('H40', '8ec9a00bfd09b3190ac6b22251dbb1aa95a0579d')
                    // flags
                    . pack('n', (0 << 15) | (0 << 14) | (0 << 12) | 9),
                'path' => 'README.md',
                'expected' => pack('N*', 1744515851, 123, 1744515852, 456, 16777232, 63058704, 33188)
                    // uid, gid, size
                    . pack('N*', 501, 20, 52)
                    // objectName
                    . pack('H40', '8ec9a00bfd09b3190ac6b22251dbb1aa95a0579d')
                    // flags
                    . pack('n', (0 << 15) | (0 << 14) | (0 << 12) | 9)
                    . "README.md\0",
            ],
        ]);

    it(
        'fails to over path size, throws InvalidArgumentException',
        function (string $blob, string $path, Throwable $expected) {
            $header = IndexEntryHeader::parse($blob);
            $entry = IndexEntry::parse($header, $path);

            expect(fn() => $entry->asBlob())->toThrow($expected);
        }
    )
        ->with([
            [
                // ctime, ctimeNano, mtime, mtimeNano, dev, ino, mode
                pack('N*', 1744515851, 123, 1744515852, 456, 16777232, 63058704, 33188)
                    // uid, gid, size
                    . pack('N*', 501, 20, 52)
                    // objectName
                    . pack('H40', '8ec9a00bfd09b3190ac6b22251dbb1aa95a0579d')
                    // flags
                    . pack('n', (0 << 15) | (0 << 14) | (0 << 12) | 4095),
                str_repeat('a', 4096),
                new InvalidArgumentException('the path length exceed of limit: 4096')
            ]
        ]);
});

describe('flags', function () {
    it('match to new IndexEntry flags', function (
        FileStat $fileStat,
        ObjectHash $objectHash,
        TrackingPath $trackingPath,
        int $expected
    ) {
        $entry = IndexEntry::new($fileStat, $objectHash, $trackingPath);

        expect($entry->flags())->toBe($expected);
    })
        ->with([
            [
                'fileStat' => FileStat::new([
                    'dev' => 16777232,
                    'ino' => 63058704,
                    'mode' => 33188,
                    'nlink' => 1,
                    'uid' => 501,
                    'gid' => 20,
                    'rdev' => 0,
                    'size' => 52,
                    'atime' => 1744515851,
                    'mtime' => 1744515852,
                    'ctime' => 1744515853,
                    'blksize' => 4096,
                    'blocks' => 8,
                ]),
                'objectHash' => ObjectHash::parse('243182b9d0b085c06005bf773212854bf7cd4694'),
                'trackingPath' => TrackingPath::new('README.md'),
                'expected' => 0,
            ],

        ]);

    it('match to parse IndexEntry flags', function (string $blob, string $path, int $expected) {
        $header = IndexEntryHeader::parse($blob);
        $entry = IndexEntry::parse($header, $path);

        expect($entry->flags())->toBe($expected);
    })
        ->with([
            [
                // ctime, ctimeNano, mtime, mtimeNano, dev, ino, mode
                'blob' => pack('N*', 1745070011, 0, 1744383756, 0, 16777233, 63467197, 33261)
                    // uid, gid, size
                    . pack('N*', 502, 21, 53)
                    // objectName
                    . pack('H40', '8ec9a00bfd09b3190ac6b22251dbb1aa95a0579d')
                    // flags
                    . pack('n', (0 << 15) | (0 << 14) | (0 << 12) | 11),
                'path' => 'src/main.go',
                'expected' => 0,
            ],
        ]);
});
