<?php

declare(strict_types=1);

namespace Phpgit\Domain;

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
