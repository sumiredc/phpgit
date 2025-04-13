<?php

declare(strict_types=1);

namespace Phpgit\Repository;

use Phpgit\Domain\GitIndex;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\Repository\IndexRepositoryInterface;
use RuntimeException;

readonly final class IndexRepository implements IndexRepositoryInterface
{
    public function save(GitIndex $gitIndex): void
    {
        if (file_put_contents(F_GIT_INDEX, $gitIndex->blob()) === false) {
            throw new RuntimeException('failed to save Git Index');
        };
    }

    public function get(): GitIndex
    {
        $handle = fopen(F_GIT_INDEX, 'rb');
        if ($handle === false) {
            throw new RuntimeException('failed to fopen Git Index');
        }

        $header = fread($handle, 12);
        if ($header === false) {
            throw new RuntimeException('failed to fread Git Index header');
        }

        [$gitIndex, $entityCount] = GitIndex::parse($header);

        for ($i = 0; $i < $entityCount; $i++) {
            $entryHeaderBlob = fread($handle, 64);
            if ($entryHeaderBlob === false) {
                throw new RuntimeException('failed to fread Entry header');
            }

            $entryHeader = IndexEntry::parseHeader($entryHeaderBlob);

            $pathLength = IndexEntry::parsePathLength($entryHeader['flags']);
            $pathWithNull = fread($handle, $pathLength + 1);
            if ($pathWithNull === false) {
                throw new RuntimeException('failed to fread Entry path');
            }

            $path = substr($pathWithNull, 0, -1); // remove to null-terminated string

            // skipp padding()
            $entrySize = 64 + $pathLength + 1; // 1byte is null-terminated string
            $paddingLength = (8 - ($entrySize % 8)) % 8;

            $indexEntry = IndexEntry::parse($entryHeader, $path);
            $gitIndex->addEntry($indexEntry);

            if ($paddingLength > 0 && fread($handle, $paddingLength) === false) {
                throw new RuntimeException('failed to fread Entry padding');
            }
        }

        if (!fclose($handle)) {
            throw new RuntimeException('failed to fclose');
        }

        return $gitIndex;
    }

    public function exists(): bool
    {
        return is_file(F_GIT_INDEX);
    }

    public function create(): GitIndex
    {
        if (!touch(F_GIT_INDEX)) {
            throw new RuntimeException('failed to create index');
        }

        $gitIndex = GitIndex::make();
        $this->save($gitIndex);

        return $gitIndex;
    }

    public function getOrCreate(): GitIndex
    {
        if ($this->exists()) {
            return $this->get();
        }

        return $this->create();
    }
}
