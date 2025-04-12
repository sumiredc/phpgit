<?php

declare(strict_types=1);

namespace Phpgit\Domain;

use InvalidArgumentException;
use RuntimeException;

final class GitIndex
{
    /** 
     * @var array<string,IndexEntry> key is filename
     */
    private array $indexEntries = [];

    private function __construct(
        public readonly string $signature,
        public readonly int $version
    ) {}

    public static function make(): self
    {
        return new self(GIT_INDEX_SIGNATURE, GIT_INDEX_VERSION);
    }

    /** 
     * @return array{
     *  0:self,
     *  1:int,  index entity count
     * }
     * @throws InvalidArgumentException
     */
    public static function parse(string $headerBlob): array
    {
        $header = unpack('a4signature/Nversion/Ncount', $headerBlob);
        if ($header === false) {
            throw new InvalidArgumentException('failed to unpack Git Index header');
        }

        $signature = $header['signature'] ?? '';
        if ($signature !== GIT_INDEX_SIGNATURE) {
            throw new RuntimeException(sprintf('invalid signature in git index: %s', $signature));
        }

        $version = $header['version'] ?? 0;
        if ($version !== GIT_INDEX_VERSION) {
            throw new RuntimeException(sprintf('invalid varsion in git index: %d', $version));
        }

        return [new self($signature, $version), intval($header['count'])];
    }

    /** @return array<IndexEntry> */
    public function entries(): array
    {
        return $this->indexEntries;
    }

    public function addEntry(IndexEntry $indexEntry): int
    {
        $this->indexEntries[$indexEntry->trackingFile->path] = $indexEntry;

        // sort path in asc
        ksort($this->indexEntries, SORT_STRING);

        return count($this->indexEntries);
    }

    public function headerBlob(): string
    {
        return pack(
            'a4NN',
            $this->signature,
            $this->version,
            count($this->indexEntries)
        );
    }

    public function entriesBlob(): string
    {
        return array_reduce(
            $this->indexEntries,
            fn(string $blob, IndexEntry $entry) => sprintf('%s%s', $blob, $entry->blob()),
            ''
        );
    }

    public function blob(): string
    {
        $data = sprintf('%s%s', $this->headerBlob(), $this->entriesBlob());
        $checksum = hash('sha1', $data, true);

        return sprintf('%s%s', $data, $checksum);
    }
}
