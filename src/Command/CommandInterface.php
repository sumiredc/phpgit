<?php

declare(strict_types=1);

namespace Phpgit\Command;

use Closure;
use InvalidArgumentException;

interface CommandInterface
{
    /**
     * @throws InvalidArgumentException
     */
    public function addOption(
        string $name,
        string|array|null $shortcut = null,
        ?int $mode = null,
        string $description = '',
        mixed $default = null,
        array|Closure $suggestedValues = []
    ): static;

    /**
     * @throws InvalidArgumentException
     */
    public function addArgument(
        string $name,
        ?int $mode = null,
        string $description = '',
        mixed $default = null,
        array|\Closure $suggestedValues = []
    ): static;
}
