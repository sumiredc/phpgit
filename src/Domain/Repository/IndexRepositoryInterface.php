<?php

declare(strict_types=1);

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

    /** @throws RuntimeException */
    public function create(): GitIndex;

    /** @throws RuntimeException */
    public function getOrCreate(): GitIndex;
}
