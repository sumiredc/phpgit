<?php

declare(strict_types=1);

namespace Phpgit\Module\LineDiff\Domain;

readonly final class DiffResultDetail
{
    public function __construct(
        public readonly Operation $operation,
        public readonly int $line,
        public readonly string $string,
    ) {}
}
