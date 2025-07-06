<?php

declare(strict_types=1);

namespace Phpgit\Infra\Repository;


use Phpgit\Domain\GitIndex;
use Phpgit\Domain\GitIndexHeader;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\IndexEntryHeader;
use Phpgit\Domain\IndexEntryPathSize;
use Phpgit\Domain\IndexEntrySize;
use Phpgit\Domain\IndexPaddingSize;
use Phpgit\Domain\Repository\IndexRepositoryInterface;
use RuntimeException;

readonly final class IndexRepository implements IndexRepositoryInterface
{
    public function save(GitIndex $gitIndex): void
    {
        if (file_put_contents(F_GIT_INDEX, $gitIndex->asBlob()) === false) {
            throw new RuntimeException('failed to save Git Index');
        };
    }

    public function get(): GitIndex
    {
        $fp = @fopen(F_GIT_INDEX, 'rb');
        if ($fp === false) {
            throw new RuntimeException('failed to fopen Git Index');
        }

        try {
            $header = fread($fp, GIT_INDEX_HEADER_LENGTH);
            if ($header === false) {
                throw new RuntimeException('failed to fread Git Index header');
            }

            $indexHeader = GitIndexHeader::parse($header);
            $gitIndex = GitIndex::parse($indexHeader);

            while (!$gitIndex->isLoadedEntries()) {
                $entryHeaderBlob = fread($fp, GIT_INDEX_ENTRY_HEADER_LENGTH);
                if ($entryHeaderBlob === false) {
                    throw new RuntimeException('failed to fread Entry header'); // @codeCoverageIgnore
                }

                $entryHeader = IndexEntryHeader::parse($entryHeaderBlob);
                $pathSize = IndexEntryPathSize::parse($entryHeader->flags);

                $pathWithNull = fread($fp, $pathSize->withNull);
                if ($pathWithNull === false) {
                    throw new RuntimeException('failed to fread Entry path'); // @codeCoverageIgnore
                }

                $path = substr($pathWithNull, 0, -1); // remove to null-terminated string

                // skipp padding
                $entrySize = IndexEntrySize::new($pathSize);
                $paddingSize = IndexPaddingSize::new($entrySize);

                $indexEntry = IndexEntry::parse($entryHeader, $path);
                $gitIndex->loadEntry($indexEntry);

                if ($paddingSize->isEmpty()) {
                    continue;
                }

                if (fread($fp, $paddingSize->value) === false) {
                    throw new RuntimeException('failed to fread Entry padding'); // @codeCoverageIgnore
                }
            }

            return $gitIndex;
        } finally {
            fclose($fp);
        }
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

        $gitIndex = GitIndex::new();
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
