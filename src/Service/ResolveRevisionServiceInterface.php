<?php

declare(strict_types=1);

namespace Phpgit\Service;

use Phpgit\Domain\ObjectHash;

interface ResolveRevisionServiceInterface
{
    public function __invoke(string $rev): ?ObjectHash;
}
