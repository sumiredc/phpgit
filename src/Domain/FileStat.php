<?php

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
            $stat['dev'],
            $stat['ino'],
            $stat['mode'],
            $stat['nlink'],
            $stat['uid'],
            $stat['gid'],
            $stat['rdev'],
            $stat['size'],
            $stat['atime'],
            $stat['mtime'],
            $stat['ctime'],
            $stat['blksize'],
            $stat['blocks'],
        );
    }
}
