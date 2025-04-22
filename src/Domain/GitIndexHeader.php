<?php

declare(strict_types=1);

namespace Phpgit\Domain;

use InvalidArgumentException;
use RuntimeException;

final class GitIndexHeader
{
    private function __construct(
        public readonly string $signature,
        public readonly int $version,
        public private(set) int $count,
    ) {}

    public static function new(): self
    {
        return new self(GIT_INDEX_SIGNATURE, GIT_INDEX_VERSION, 0);
    }

    /** 
     * @throws InvalidArgumentException
     */
    public static function parse(string $blob): self
    {
        if (strlen($blob) !== GIT_INDEX_HEADER_LENGTH) {
            throw new InvalidArgumentException(sprintf('length is not enough: %d', strlen($blob)));
        }

        $header = @unpack('a4signature/Nversion/Ncount', $blob);

        // NOTE: This branch should never be reached, as the length check ensures the input is valid for unpack().
        // @codeCoverageIgnoreStart
        if ($header === false) {
            throw new RuntimeException(sprintf('failed to unpack Git Index header: %s', $blob));
        }
        // @codeCoverageIgnoreEnd

        $signature = strval($header['signature'] ?? '');
        if ($signature !== GIT_INDEX_SIGNATURE) {
            throw new InvalidArgumentException(sprintf('invalid signature in git index signature: %s', $signature));
        }

        $version = intval($header['version'] ?? 0);
        if ($version !== GIT_INDEX_VERSION) {
            throw new InvalidArgumentException(sprintf('invalid version in git index version: %d', $version));
        }

        $count = intval($header['count'] ?? 0);

        return new self($signature, $version, $count);
    }

    /** @param array<string,IndexEntry> $entries */
    public function updateCount(array $entries): int
    {
        $this->count = count($entries);

        return $this->count;
    }

    public function asBlob(): string
    {
        return pack(
            'a4NN',
            $this->signature,
            $this->version,
            $this->count,
        );
    }
}
