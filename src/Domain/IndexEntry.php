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
    public GitFileMode $gitFileMode {
        get => match ($this->indexObjectType) {
            IndexObjectType::Normal => GitFileMode::from(decoct($this->mode)),
            IndexObjectType::GitLink => GitFileMode::SubModule,
            IndexObjectType::SymbolicLink => GitFileMode::SymbolicLink,
        };
    }

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

    public static function new(
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

    public static function parse(IndexEntryHeader $header, string $path): self
    {
        $indexObjectType = IndexObjectType::parseFlags($header->mode);
        $unixPermission = UnixPermission::parseFlags($header->mode);

        $objectHash = ObjectHash::parse($header->objectName);
        $trackingFile = TrackingFile::new($path);

        $assumeValidFlag = ($header->flags >> 15) & 0b1; // the upper 1bit 
        $extendedFlag = ($header->flags >> 14) & 0b1; // 1bit from the two upper
        $stage = ($header->flags >> 12) & 0b11; // 2bit from the four upper

        return new self(
            ctime: $header->ctime,
            ctimeNano: $header->ctimeNano,
            mtime: $header->mtime,
            mtimeNano: $header->mtimeNano,
            dev: $header->dev,
            ino: $header->ino,
            mode: $header->mode,
            indexObjectType: $indexObjectType,
            unixPermission: $unixPermission,
            uid: $header->uid,
            gid: $header->gid,
            size: $header->size,
            objectHash: $objectHash,
            trackingFile: $trackingFile,
            assumeValidFlag: $assumeValidFlag,
            extendedFlag: $extendedFlag,
            stage: $stage
        );
    }

    public function asBlob(): string
    {
        $ctime = pack('NN', $this->ctime, $this->ctimeNano);
        $mtime = pack('NN', $this->mtime, $this->mtimeNano);
        $meta = pack('NN', $this->dev, $this->ino);

        $objectType = $this->indexObjectType->asStorableValue();
        $unused = 0b000 << 9; # 3bit empty
        $permission = $this->unixPermission->value; # 9bit
        $mode = pack('N', $objectType | $unused | $permission);

        $ids = pack('NN', $this->uid, $this->gid);
        $size = pack('N', $this->size);

        $objectName = hex2bin($this->objectHash->value); # 20byte
        if ($objectName === false) {
            throw new RuntimeException(sprintf('failed to hex2bin: %s', $this->objectHash->value));
        }

        $pathSize = IndexEntryPathSize::new($this->trackingFile->path);
        if ($pathSize->isOverFlagsSpace()) {
            // TODO: パスの上限値を釣果した場合は、1文字ずつnull終端まで取得する処理を導入して解消させる
            throw new InvalidArgumentException(sprintf('the path length exceed of limit: %d', $pathSize->value));
        }

        $assumeValidFlag = 0 << 15;
        $extendedFlag = 0 << 14;
        $stage = 0 << 12;
        $flags = pack('n', $assumeValidFlag | $extendedFlag | $stage | $pathSize->asStorableValue());
        $path = sprintf("%s\0", $this->trackingFile->path);
        $entrySize = IndexEntrySize::new($pathSize);
        $paddingSize = IndexPaddingSize::new($entrySize);

        return implode('', [
            $ctime,
            $mtime,
            $meta,
            $mode,
            $ids,
            $size,
            $objectName,
            $flags,
            $path,
            $paddingSize->asPadding(),
        ]);
    }

    /**
     * NOTE: only flags (ignore path size)
     */
    public function flags(): int
    {
        return $this->assumeValidFlag << 15
            | $this->extendedFlag << 14
            | $this->stage << 12;
    }
}
