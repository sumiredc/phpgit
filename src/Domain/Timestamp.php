<?php

declare(strict_types=1);

namespace Phpgit\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use UnderflowException;

readonly final class Timestamp
{
    private function __construct(
        public readonly int $value,
        public readonly string $offset
    ) {}

    public static function new(): self
    {
        $now = new DateTimeImmutable();

        return new self($now->getTimestamp(), $now->format("O"));
    }

    /** 
     * @throws UnderflowException
     * @throws InvalidArgumentException
     */
    public static function parse(int $timestamp, string $offset): self
    {
        if ($timestamp < 0) {
            throw new UnderflowException(sprintf('the lower bound for timestamps is 0: %d', $timestamp));
        }

        if (!preg_match('/^[\+|\-]\d{4}$/', $offset, $matches)) {
            throw new InvalidArgumentException(sprintf('not offset pattern: %s', $offset));
        }

        return new self($timestamp, $offset);
    }


    public function toRawString(): string
    {
        return sprintf('%d %s', $this->value, $this->offset);
    }
}
