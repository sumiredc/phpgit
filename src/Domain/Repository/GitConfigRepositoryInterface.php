<?php

declare(strict_types=1);

namespace Phpgit\Domain\Repository;

use ParseError;
use Phpgit\Domain\GitConfig;
use RuntimeException;

interface GitConfigRepositoryInterface
{
    /** 
     * @throws RuntimeException 
     * @throws ParseError
     */
    public function get(): GitConfig;

    /** @throws RuntimeException */
    public function create(): void;
}
