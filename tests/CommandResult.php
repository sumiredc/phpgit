<?php

declare(strict_types=1);

namespace Tests;

readonly final class CommandResult
{
    public function __construct(
        public readonly string $output,
        public readonly int $exitCode
    ) {}
}
