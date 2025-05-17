<?php

declare(strict_types=1);

namespace Phpgit\Domain;

use AssertionError;
use OverflowException;

final class GitIndex
{
    /**
     * @var array<string,IndexEntry> key is filename
     */
    public private(set) array $entries = [];

    public int $count {
        get => $this->header->count;
    }

    public int $version {
        get => $this->header->version;
    }

    public string $signature {
        get => $this->header->signature;
    }

    private function __construct(
        private GitIndexHeader $header
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

    /**
     * Loads an index entry from the index file.
     * 
     * This method is used during index restoration to set existing entries
     * without modifying the header's entry count. It assumes the count has
     * already been read from the index file and enforces that no more than
     * that number of entries are loaded.
     * 
     * @throws OverflowException
     */
    public function loadEntry(IndexEntry $indexEntry): int
    {
        if ($this->isLoadedEntries()) {
            throw new OverflowException('Too many entries loaded from index file');
        }

        $this->entries[$indexEntry->trackingFile->path] = $indexEntry;

        return count($this->entries);
    }

    public function isLoadedEntries(): bool
    {
        return count($this->entries) >= $this->count;
    }

    /**
     * @throws AssertionError
     */
    public function assert(): void
    {
        if ($this->isLoadedEntries()) {
            return;
        }

        throw new AssertionError(sprintf(
            'Expected %d index entries, but only %d were loaded',
            $this->count,
            count($this->entries)
        ));
    }

    /**
     * Adds a new entry to the index.
     * 
     * This method is used to track new files by adding them to the index,
     * and updates the header's entry count accordingly. It does not check
     * against any existing maximum count since it is not part of file restoration.
     */
    public function addEntry(IndexEntry $indexEntry): int
    {
        $this->entries[$indexEntry->trackingFile->path] = $indexEntry;

        // sort path in asc
        ksort($this->entries, SORT_STRING);

        return $this->header->updateCount($this->entries);
    }

    public function entriesBlob(): string
    {
        return array_reduce(
            $this->entries,
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
        return array_key_exists($trackingFile->path, $this->entries);
    }

    public function existsEntryByFilename(string $file): bool
    {
        return array_key_exists($file, $this->entries);
    }

    public function removeEntryByFilename(string $file): void
    {
        unset($this->entries[$file]);
    }
}
