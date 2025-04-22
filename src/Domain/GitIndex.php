<?php

declare(strict_types=1);

namespace Phpgit\Domain;

final class GitIndex
{
    private array $indexEntries = [];

    /**
     * @var array<string,IndexEntry> key is filename
     */
    public array $entries {
        get => $this->indexEntries;
    }

    public int $count {
        get => $this->header->count;
    }

    private function __construct(
        public private(set) GitIndexHeader $header
    ) {}

    public static function new(): self
    {
        $header = GitIndexHeader::new();
        return new self($header);
    }

    public static function parse(GitIndexHeader $header): self
    {
        return new self($header);
    }

    public function addEntry(IndexEntry $indexEntry): int
    {
        $this->indexEntries[$indexEntry->trackingFile->path] = $indexEntry;

        // sort path in asc
        ksort($this->indexEntries, SORT_STRING);

        return $this->header->updateCount($this->indexEntries);
    }

    public function entriesBlob(): string
    {
        return array_reduce(
            $this->indexEntries,
            fn(string $blob, IndexEntry $entry) => sprintf('%s%s', $blob, $entry->asBlob()),
            ''
        );
    }

    public function asBlob(): string
    {
        $data = sprintf('%s%s', $this->header->asBlob(), $this->entriesBlob());
        $checksum = hash('sha1', $data, true);

        return sprintf('%s%s', $data, $checksum);
    }

    public function existsEntry(TrackingFile $trackingFile): bool
    {
        return array_key_exists($trackingFile->path, $this->indexEntries);
    }

    public function existsEntryByFilename(string $file): bool
    {
        return array_key_exists($file, $this->indexEntries);
    }

    public function removeEntryByFilename(string $file): void
    {
        unset($this->indexEntries[$file]);
    }
}
