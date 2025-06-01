<?php

declare(strict_types=1);

namespace Phpgit\Infra\Repository;

use Phpgit\Domain\Repository\GitResourceRepositoryInterface;
use RuntimeException;

readonly final class GitResourceRepository implements GitResourceRepositoryInterface
{
    public function existsGitDir(): bool
    {
        return is_dir(F_GIT_DIR);
    }

    /** @throws RuntimeException */
    public function makeGitObjectDir(): void
    {
        if (@mkdir(F_GIT_OBJECTS_DIR, 0755, true)) {
            return;
        }

        throw new RuntimeException(sprintf('failed to mkdir: %s', F_GIT_OBJECTS_DIR));
    }

    /** @throws RuntimeException */
    public function makeGitHeadsDir(): void
    {
        if (@mkdir(F_GIT_REFS_HEADS_DIR, 0755, true)) {
            return;
        }

        throw new RuntimeException(sprintf('failed to mkdir: %s', F_GIT_REFS_HEADS_DIR));
    }

    /** @throws RuntimeException */
    public function createGitHead(): void
    {
        if (@touch(F_GIT_HEAD)) {
            return;
        }

        throw new RuntimeException(sprintf('failed to touch: %s', F_GIT_HEAD));
    }

    /** @throws RuntimeException */
    public function saveGitHead(string $branch): void
    {
        $data = sprintf('ref: %s/%s', GIT_REFS_HEADS_DIR, $branch);

        if (@file_put_contents(F_GIT_HEAD, $data) === false) {
            throw new RuntimeException(sprintf('failed to write in file: %s', F_GIT_HEAD));
        }
    }
}
