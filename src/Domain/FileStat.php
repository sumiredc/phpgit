<?php

declare(strict_types=1);

namespace Phpgit\Domain;

readonly final class FileStat
{
    private function __construct(
        public readonly int $dev,
        public readonly int $ino,
        public readonly int $mode,
        public readonly int $nlink,
        public readonly int $uid,
        public readonly int $gid,
        public readonly int $rdev,
        public readonly int $size,
        public readonly int $atime,
        public readonly int $mtime,
        public readonly int $ctime,
        public readonly int $blksize,
        public readonly int $blocks,
    ) {}

    /** 
     * @param array{
     *  dev: int, 
     *  ino: int, 
     *  mode: int, 
     *  nlink: int, 
     *  uid: int, 
     *  gid: int, 
     *  rdev: int, 
     *  size: int, 
     *  atime: int, 
     *  mtime: int, 
     *  ctime: int, 
     *  blksize: int, 
     *  blocks: int
     * } $stat
     */
    public static function make(array $stat): self
    {
        return new self(
            dev: $stat['dev'],
            ino: $stat['ino'],
            mode: $stat['mode'],
            nlink: $stat['nlink'],
            uid: $stat['uid'],
            gid: $stat['gid'],
            rdev: $stat['rdev'],
            size: $stat['size'],
            atime: $stat['atime'],
            mtime: $stat['mtime'],
            ctime: $stat['ctime'],
            blksize: $stat['blksize'],
            blocks: $stat['blocks'],
        );
    }

    public static function makeForCacheinfo(int $mode): self
    {
        return new self(
            dev: 0,
            ino: 0,
            mode: $mode,
            nlink: 0,
            uid: 0,
            gid: 0,
            rdev: 0,
            size: 0,
            atime: 0,
            mtime: 0,
            ctime: 0,
            blksize: 0,
            blocks: 0,
        );
    }
}
