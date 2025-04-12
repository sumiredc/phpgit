<?php

declare(strict_types=1);

namespace Phpgit\Repository;

use Phpgit\Domain\GitIndex;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\Repository\IndexRepositoryInterface;
use RuntimeException;

final class IndexRepository implements IndexRepositoryInterface
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

            $pathLength = IndexEntry::parsepathLength($entryHeader['flags']);
            $path = fread($handle, $pathLength);
            if ($path === false) {
                throw new RuntimeException('failed to fread Entry path');
            }

            // skipp padding
            $entrySize = 62 + $pathLength;
            $padding = (8 - ($entrySize % 8)) % 8;

            $indexEntry = IndexEntry::parse($entryHeader, $path);
            $gitIndex->addEntry($indexEntry);

            if (fread($handle, $padding) === false) {
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

    public function createEmpty(): bool
    {
        return touch(F_GIT_INDEX);
    }
}
