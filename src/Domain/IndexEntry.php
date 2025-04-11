<?php

namespace Phpgit\Domain;

use Phpgit\Domain\FileStat;
use Phpgit\Domain\IndexObjectType;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\TrackingFile;
use Phpgit\Domain\UnixPermission;
use RuntimeException;

/**
 * @version 2.37.2
 * @see https://git-scm.com/docs/index-format
 * @see https://www.php.net/manual/en/function.pack.php
 */
final class IndexEntry
{
    private function __construct(
        public readonly int $ctime,
        public readonly int $ctimeNano,
        public readonly int $mtime,
        public readonly int $mtimeNano,
        public readonly int $dev,
        public readonly int $ino,
        public readonly int $mode,
        public readonly IndexObjectType $indexObjectType,
        public readonly UnixPermission $unixPermission,
        public readonly int $uid,
        public readonly int $gid,
        public readonly int $size,
        public readonly ObjectHash $objectHash,
        public readonly TrackingFile $trackingFile,
    ) {}

    public static function make(
        FileStat $fileStat,
        ObjectHash $objectHash,
        TrackingFile $trackingFile
    ) {
        return new self(
            ctime: $fileStat->ctime,
            ctimeNano: 0,
            mtime: $fileStat->mtime,
            mtimeNano: 0,
            dev: $fileStat->dev,
            ino: $fileStat->ino,
            mode: $fileStat->mode,
            indexObjectType: IndexObjectType::Normal,
            unixPermission: UnixPermission::fromStatMode($fileStat->mode),
            uid: $fileStat->uid,
            gid: $fileStat->gid,
            size: $fileStat->size,
            objectHash: $objectHash,
            trackingFile: $trackingFile,
        );
    }

    /**
     * @param array{
     *  ctime_sec: int,
     *  ctime_nsec: int,
     *  mtime_sec: int,
     *  mtime_nsec: int,
     *  dev: int,
     *  ino: int,
     *  mode: int,
     *  object: int,
     *  uid: int,
     *  gid: int,
     *  size: int,
     *  object_name: string,
     *  flags: int,
     * } $entryHeader
     */
    public static function parse(array $entryHeader, string $path): self
    {
        $objectType = ($entryHeader['object'] >> 12) & 0b1111;
        $permission = $entryHeader['object'] & 0b1_1111_1111;

        $indexObjectType = IndexObjectType::from($objectType);
        $unixPermission = UnixPermission::fromDecoct($permission);
        $objectHash = ObjectHash::parse($entryHeader['object_name']);
        $trackingFile = TrackingFile::parse($path);

        return new self(
            ctime: $entryHeader['ctime_sec'],
            ctimeNano: $entryHeader['ctime_nsec'],
            mtime: $entryHeader['mtime_sec'],
            mtimeNano: $entryHeader['mtime_nsec'],
            dev: $entryHeader['dev'],
            ino: $entryHeader['ino'],
            mode: $entryHeader['mode'],
            indexObjectType: $indexObjectType,
            unixPermission: $unixPermission,
            uid: $entryHeader['uid'],
            gid: $entryHeader['gid'],
            size: $entryHeader['size'],
            objectHash: $objectHash,
            trackingFile: $trackingFile,
        );
    }

    /**
     * @return array{
     *  ctime_sec: int,
     *  ctime_nsec: int,
     *  mtime_sec: int,
     *  mtime_nsec: int,
     *  dev: int,
     *  ino: int,
     *  mode: int,
     *  object: int,
     *  uid: int,
     *  gid: int,
     *  size: int,
     *  object_name: string,
     *  flags: string,
     * }
     */
    public static function parseHeader(string $entryHeaderBlob): array
    {
        $header = unpack(
            implode('/', [
                'Nctime_sec',
                'Nctime_nsec',
                'Nmtime_sec',
                'Nmtime_nsec',
                'Ndev',
                'Nino',
                'Nmode',
                'nobject',
                'Nuid',
                'Ngid',
                'Nsize',
                'H40object_name',
                'nflags'
            ]),
            $entryHeaderBlob
        );

        if ($header === false) {
            throw new RuntimeException('failed to unpack Entry header');
        }

        return $header;
    }

    /** NOTE: Path length is lowest 12 bit in flags */
    public static function parsePathLength(string $flags): int
    {
        return $flags & 0x0FFF;
    }

    public function blob(): string
    {
        $ctime = pack('NN', $this->ctime, $this->ctimeNano);
        $mtime = pack('NN', $this->mtime, $this->mtimeNano);
        $meta = pack('NNN', $this->dev, $this->ino, $this->mode);

        $objectType = $this->indexObjectType->value << 12; # 4bit
        $unused = 0b000 << 9; # 3bit empty
        $permission = $this->unixPermission->value; # 9bit
        $object = pack('n', $objectType | $unused | $permission);

        $ids = pack('NN', $this->uid, $this->gid);
        $size = pack('N', $this->size);

        $objectName = hex2bin($this->objectHash->value()); # 20byte
        if ($objectName === false) {
            throw new RuntimeException('failed to hex2bin: %s', $this->objectHash->value());
        }

        $flags = pack('n', min(strlen($this->trackingFile->path), 0xFFF));
        $path = sprintf('%s\0', $this->trackingFile->path);
        $padding = str_repeat('\0', (8 - (strlen($path) + 62) % 8) % 8);

        return implode('', [
            $ctime,
            $mtime,
            $meta,
            $object,
            $ids,
            $size,
            $objectName,
            $flags,
            $path,
            $padding
        ]);
    }
}
