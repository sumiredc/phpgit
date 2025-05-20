<?php

declare(strict_types=1);

use Phpgit\Domain\FileStat;
use Phpgit\Domain\GitIndexHeader;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\TrackedPath;

describe('new', function () {
    it('should match to properties', function (string $signature, int $version, int $count) {
        $actual = GitIndexHeader::new();

        expect($actual->signature)->toBe($signature);
        expect($actual->version)->toBe($version);
        expect($actual->count)->toBe($count);
    })
        ->with([
            [GIT_INDEX_SIGNATURE, GIT_INDEX_VERSION, 0]
        ]);
});

describe('parse', function () {
    it('should match to properties', function (
        string $blob,
        string $signature,
        int $version,
        int $count
    ) {
        $actual = GitIndexHeader::parse($blob);

        expect($actual->signature)->toBe($signature);
        expect($actual->version)->toBe($version);
        expect($actual->count)->toBe($count);
    })
        ->with([
            [
                'blob' => pack('a4NN', GIT_INDEX_SIGNATURE, GIT_INDEX_VERSION, 12),
                'signature' => GIT_INDEX_SIGNATURE,
                'version' => GIT_INDEX_VERSION,
                'count' => 12
            ]
        ]);

    it('fails to parse', function (string $blob, string $messageFormat, int|string $failedValue) {
        expect(fn() =>  GitIndexHeader::parse($blob))
            ->toThrow(InvalidArgumentException::class, sprintf($messageFormat, $failedValue));
    })
        ->with([
            'invalid length' => [
                'blob' => pack('a4Nn', GIT_INDEX_SIGNATURE, GIT_INDEX_VERSION, 0),
                'messageFormat' => 'length is not enough: %d',
                'failedValue' => 10
            ],
            'invalid signature' => [
                'blob' => pack('a4NN', 'DDDD', GIT_INDEX_VERSION, 0),
                'messageFormat' => 'nvalid signature in git index signature: %s',
                'failedValue' => 'DDDD'
            ],
            'invalid version' => [
                'blob' => pack('a4NN', GIT_INDEX_SIGNATURE, 3, 10),
                'messageFormat' => 'invalid version in git index version: %d',
                'failedValue' => 3
            ],
        ]);
});

describe('updateCount', function () {
    it('should match to count', function (array $entries, int $expected) {
        $header = GitIndexHeader::new();
        $actual = $header->updateCount($entries);

        expect($actual)->toBe($expected);
    })
        ->with([
            [
                'entries' => [],
                'expected' => 0
            ],
            [
                'entries' => array_fill(0, 10, IndexEntry::new(
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
                        'blocks' => 8
                    ]),
                    ObjectHash::new('8ec9a00bfd09b3190ac6b22251dbb1aa95a0579d'),
                    TrackedPath::parse('README.md')
                )),
                'expected' => 10
            ],
            [
                'entries' => array_fill(0, 20, IndexEntry::new(
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
                    TrackedPath::parse('README.md')
                )),
                'expected' => 20
            ]
        ]);
});

describe('asBlob', function () {
    it('should match to blob, from new header', function (string $expected) {
        $actual = GitIndexHeader::new();

        expect($actual->asBlob())->toBe($expected);
    })
        ->with([
            [pack('a4NN', GIT_INDEX_SIGNATURE, GIT_INDEX_VERSION, 0)],
        ]);

    it('should match to blob, from parse header', function (string $blob) {
        $actual = GitIndexHeader::parse($blob);

        expect($actual->asBlob())->toBe($blob);
    })
        ->with([
            [pack('a4NN', GIT_INDEX_SIGNATURE, GIT_INDEX_VERSION, 0)],
            [pack('a4NN', GIT_INDEX_SIGNATURE, GIT_INDEX_VERSION, 100)],
        ]);
});
