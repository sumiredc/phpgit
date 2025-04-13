<?php

declare(strict_types=1);

namespace Phpgit\Domain\Repository;

interface GitResourceRepositoryInterface
{
    public function existsGitDir(): bool;

    public function makeGitObjectDir(): void;

    public function makeGitHeadsDir(): void;

    public function createGitHead(): void;

    public function saveGitHead(string $branch): void;
}
