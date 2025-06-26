<?php

declare(strict_types=1);

namespace Phpgit\Module\LineDiff\Domain;

final class DiffResult
{
    private const INSERT_SYMBOL = '+';
    private const DELETE_SYMBOL = '-';

    /**
     * @var array<DiffResultDetail>
     */
    public private(set) array $details = [];

    public function reverse(): self
    {
        $this->details = array_reverse($this->details);

        return $this;
    }

    public function add(Operation $operation, int $line, string $string): void
    {
        $this->details[] = new DiffResultDetail($operation, $line, $string);
    }

    public function toUnifiedString(): string
    {
        $lines = array_map(fn(DiffResultDetail $d) => match ($d->operation) {
            Operation::Insert => self::INSERT_SYMBOL . " {$d->string}",
            Operation::Delete => self::DELETE_SYMBOL . " {$d->string}",
            default => $d->string
        }, $this->details);

        return implode("\n", $lines);
    }
}
