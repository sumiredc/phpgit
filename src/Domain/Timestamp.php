<?php

declare(strict_types=1);

namespace Phpgit\Domain;

use DateTime;

readonly final class Timestamp
{
    private function __construct(
        public readonly int $value,
        public readonly string $offset
    ) {}

    public static function new(): self
    {
        $now = new DateTime();
        $timestamp = $now->getTimestamp();

        $offsetSeconds = $now->getOffset();
        $sign = ($offsetSeconds < 0) ? '-' : '+';
        $absOffset = abs($offsetSeconds);
        $hours = floor($absOffset / 3600);
        $minutes = ($absOffset % 3600) / 60;

        return new self(
            $timestamp,
            sprintf('%s%02d%02d', $sign, $hours, $minutes),
        );
    }

    public function toRawString(): string
    {
        return sprintf('%d %s', $this->value, $this->offset);
    }
}
