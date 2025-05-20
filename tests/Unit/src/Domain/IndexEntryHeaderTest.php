<?php

declare(strict_types=1);

use Phpgit\Domain\IndexEntryHeader;


describe('parse', function () {
    it(
        'should match to properties',
        function (
            string $blob,
            int $ctime,
            int $ctimeNano,
            int $mtime,
            int $mtimeNano,
            int $dev,
            int $ino,
            int $mode,
            int $uid,
            int $gid,
            int $size,
            string $objectName,
            int $flags,
        ) {
            $actual = IndexEntryHeader::parse($blob);

            expect($actual->ctime)->toBe($ctime);
            expect($actual->ctimeNano)->toBe($ctimeNano);
            expect($actual->mtime)->toBe($mtime);
            expect($actual->mtimeNano)->toBe($mtimeNano);
            expect($actual->dev)->toBe($dev);
            expect($actual->ino)->toBe($ino);
            expect($actual->mode)->toBe($mode);
            expect($actual->uid)->toBe($uid);
            expect($actual->gid)->toBe($gid);
            expect($actual->size)->toBe($size);
            expect($actual->objectName)->toBe($objectName);
            expect($actual->flags)->toBe($flags);
        }
    )
        ->with([
            [
                // ctime, ctimeNano, mtime, mtimeNano, dev, ino, mode,
                'blob' => pack('N*', 1744515851, 0, 1744515851, 0, 16777232, 63058704,  33188)
                    // uid, gid, size
                    . pack('N*', 501, 20, 52)
                    // objectName
                    . pack('H40', '8ec9a00bfd09b3190ac6b22251dbb1aa95a0579d')
                    // flags
                    . pack('n', (0 << 15) | (0 << 14) | (0 << 12) | 7),
                'ctime' => 1744515851,
                'ctimeNano' => 0,
                'mtime' => 1744515851,
                'mtimeNano' => 0,
                'dev' => 16777232,
                'ino' => 63058704,
                'mode' => 33188,
                'uid' => 501,
                'gid' => 20,
                'size' => 52,
                'objectName' => '8ec9a00bfd09b3190ac6b22251dbb1aa95a0579d',
                'flags' => 7,
            ]
        ]);


    it(
        'fails to parse',
        function (string $blob, Throwable $expected) {
            expect(fn() => IndexEntryHeader::parse($blob))->toThrow($expected);
        }
    )
        ->with([
            'fails pack' => ['', new InvalidArgumentException('length is not enough: 0')],
        ]);
});
