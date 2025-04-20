<?php

declare(strict_types=1);

namespace Phpgit\Domain;

use InvalidArgumentException;
use RuntimeException;

readonly final class IndexEntryHeader
{
    private function __construct(
        public readonly int $ctime,
        public readonly int $ctimeNano,
        public readonly int $mtime,
        public readonly int $mtimeNano,
        public readonly int $dev,
        public readonly int $ino,
        public readonly int $mode,
        public readonly int $uid,
        public readonly int $gid,
        public readonly int $size,
        public readonly string $objectName,
        public readonly int $flags,
    ) {}

    public static function parse(string $blob): self
    {
        if (strlen($blob) !== GIT_INDEX_ENTRY_HEADER_LENGTH) {
            throw new InvalidArgumentException(sprintf('length is not enough: %d', strlen($blob)));
        }

        $packed = @unpack(
            implode('/', [
                'Nctime',
                'Nctime_nano',
                'Nmtime',
                'Nmtime_nano',
                'Ndev',
                'Nino',
                'Nmode',
                'Nuid',
                'Ngid',
                'Nsize',
                'H40object_name',
                'nflags'
            ]),
            $blob
        );

        // NOTE: This branch should never be reached, as the length check ensures the input is valid for unpack().
        // @codeCoverageIgnoreStart
        if ($blob === false) {
            throw new RuntimeException(sprintf('failed to unpack Entry header: %s', $blob));
        }
        // @codeCoverageIgnoreEnd

        return new self(
            ctime: $packed['ctime'],
            ctimeNano: $packed['ctime_nano'],
            mtime: $packed['mtime'],
            mtimeNano: $packed['mtime_nano'],
            dev: $packed['dev'],
            ino: $packed['ino'],
            mode: $packed['mode'],
            uid: $packed['uid'],
            gid: $packed['gid'],
            size: $packed['size'],
            objectName: $packed['object_name'],
            flags: $packed['flags'],
        );
    }
}
