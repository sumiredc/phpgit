<?php

declare(strict_types=1);

namespace Phpgit\Domain;

use RuntimeException;

readonly final class CompressedPayload
{
    private function __construct(public readonly string $value) {}

    public static function new(string $compressed): self
    {
        return new self($compressed);
    }

    /** @throws RuntimeException */
    public static function fromOriginal(string $original): self
    {
        $compressed = gzcompress($original);
        // NOTE: This branch should never be reached, as the length check ensures the input is valid for unpack().
        // @codeCoverageIgnoreStart
        if ($compressed === false) {
            throw new RuntimeException('failed to compress object', 500);
        }
        // @codeCoverageIgnoreEnd

        return new self($compressed);
    }

    /** @throws RuntimeException */
    public function decompress(): string
    {
        $uncompressed = zlib_decode($this->value);
        // NOTE: This branch should never be reached, as the length check ensures the input is valid for unpack().
        // @codeCoverageIgnoreStart
        if ($uncompressed === false) {
            throw new RuntimeException('failed to decompress object', 500);
        }
        // @codeCoverageIgnoreEnd

        return $uncompressed;
    }
}
