<?php

declare(strict_types=1);

namespace Phpgit\Domain;

use InvalidArgumentException;
use RuntimeException;

readonly final class CompressedPayload
{
    private function __construct(public readonly string $value) {}

    public static function new(string $compressed): self
    {
        if (@zlib_decode($compressed) === false) {
            throw new InvalidArgumentException(sprintf('not uncompressed: %s', $compressed));
        }

        return new self($compressed);
    }

    /** @throws RuntimeException */
    public static function fromOriginal(string $original): self
    {
        $compressed = @gzcompress($original);
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
        $uncompressed = @zlib_decode($this->value);
        // @codeCoverageIgnoreStart
        if ($uncompressed === false) {
            throw new RuntimeException('failed to decompress object', 500);
        }
        // @codeCoverageIgnoreEnd

        return $uncompressed;
    }
}
