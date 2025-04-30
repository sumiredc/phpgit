<?php

declare(strict_types=1);

namespace Phpgit\Domain\Repository;

use Phpgit\Domain\GitConfig;
use RuntimeException;

interface GitConfigRepositoryInterface
{
    /** @throws RuntimeException */
    public function get(): GitConfig;
}
