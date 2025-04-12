<?php

declare(strict_types=1);

namespace Phpgit\Domain;

use InvalidArgumentException;
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
        public readonly int $assumeValidFlag,
        public readonly int $extendedFlag,
        public readonly int $stage,
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
            assumeValidFlag: 0,
            extendedFlag: 0,
            stage: 0,
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
     *  object_flags: int,
     *  uid: int,
     *  gid: int,
     *  size: int,
     *  object_name: string,
     *  flags: int,
     * } $entryHeader
     */
    public static function parse(array $entryHeader, string $path): self
    {
        $objectType = ($entryHeader['object_flags'] >> 12) & 0b1111; // the upper 4bit
        $permission = $entryHeader['object_flags'] & 0b1_1111_1111; // the lower 9bit
        $indexObjectType = IndexObjectType::from($objectType);
        $unixPermission = UnixPermission::fromDecoct($permission);

        $objectHash = ObjectHash::parse($entryHeader['object_name']);
        $trackingFile = TrackingFile::parse($path);

        $assumeValidFlag = ($entryHeader['flags'] >> 15) & 0b1; // the upper 1bit 
        $extendedFlag = ($entryHeader['flags'] >> 14) & 0b1; // 1bit from the two upper
        $stage = ($entryHeader['flags'] >> 12) & 0b11; // 2bit from the four upper

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
            assumeValidFlag: $assumeValidFlag,
            extendedFlag: $extendedFlag,
            stage: $stage
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
     *  object_flags: int,
     *  uid: int,
     *  gid: int,
     *  size: int,
     *  object_name: string,
     *  flags: int,
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
                'nobject_flags',
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
    public static function parsePathLength(int $flags): int
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
            throw new RuntimeException(sprintf('failed to hex2bin: %s', $this->objectHash->value()));
        }

        $pathLength = strlen($this->trackingFile->path);
        if ($pathLength > 0xFFF) {
            // TODO: パスの上限値を釣果した場合は、1文字ずつnull終端まで取得する処理を導入して解消させる
            throw new InvalidArgumentException(sprintf('the path length exceed of limit: %d', $pathLength));
        }

        $assumeValidFlag = 0 << 15;
        $extendedFlag = 0 << 14;
        $stage = 0 << 12;
        $flags = pack('n', $assumeValidFlag | $extendedFlag | $stage | $pathLength);
        $path = sprintf("%s\0", $this->trackingFile->path);
        $entrySize = 64 + $pathLength + 1; // 1byte is null-terminated string
        $paddingLength = (8 - ($entrySize % 8)) % 8;
        $padding = str_repeat("\0", $paddingLength);

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

    public function flags(): int
    {
        return $this->assumeValidFlag << 15
            | $this->extendedFlag << 14
            | $this->stage << 12;
    }
}
