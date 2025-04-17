<?php

declare(strict_types=1);

use Phpgit\Domain\FileStat;

describe('make', function () {
    it('should match args to properties', function (array $stat) {
        $fileStat = FileStat::make($stat);

        expect($fileStat->dev)->toBe($stat['dev']);
        expect($fileStat->ino)->toBe($stat['ino']);
        expect($fileStat->mode)->toBe($stat['mode']);
        expect($fileStat->nlink)->toBe($stat['nlink']);
        expect($fileStat->uid)->toBe($stat['uid']);
        expect($fileStat->gid)->toBe($stat['gid']);
        expect($fileStat->rdev)->toBe($stat['rdev']);
        expect($fileStat->size)->toBe($stat['size']);
        expect($fileStat->atime)->toBe($stat['atime']);
        expect($fileStat->mtime)->toBe($stat['mtime']);
        expect($fileStat->ctime)->toBe($stat['ctime']);
        expect($fileStat->blksize)->toBe($stat['blksize']);
        expect($fileStat->blocks)->toBe($stat['blocks']);
    })
        ->with([
            [
                [
                    'dev' => 10,
                    'ino' => 11,
                    'mode' => 12,
                    'nlink' => 13,
                    'uid' => 14,
                    'gid' => 15,
                    'rdev' => 16,
                    'size' => 17,
                    'atime' => 18,
                    'mtime' => 19,
                    'ctime' => 20,
                    'blksize' => 21,
                    'blocks' => 22,
                ]
            ]
        ]);
});
