<?php

namespace Phpgit\Domain\Repository;

use Phpgit\Domain\GitIndex;
use RuntimeException;

interface IndexRepositoryInterface
{
    /** @throws RuntimeException */
    public function save(GitIndex $gitIndex): void;

    /** @throws RuntimeException */
    public function get(): GitIndex;

    public function exists(): bool;

    public function createEmpty(): bool;
}
