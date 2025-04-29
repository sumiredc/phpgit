<?php

declare(strict_types=1);

namespace Tests\Factory;

use Phpgit\Domain\FileStat;

final class FileStatFactory
{
    public static function new(): FileStat
    {
        return FileStat::new([
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
        ]);
    }
}
