<?php

declare(strict_types=1);

namespace Phpgit\Domain\Repository;

use RuntimeException;

interface GitResourceRepositoryInterface
{
    public function existsGitDir(): bool;

    /** @throws RuntimeException */
    public function makeGitObjectDir(): void;

    /** @throws RuntimeException */
    public function makeGitHeadsDir(): void;

    /** @throws RuntimeException */
    public function createGitHead(): void;

    /** @throws RuntimeException */
    public function saveGitHead(string $branch): void;

    /** @throws RuntimeException */
    public function createConfig(): void;
}
