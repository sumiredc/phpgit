<?php

declare(strict_types=1);

namespace Phpgit\Repository;

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
        if (mkdir(F_GIT_OBJECTS_DIR, 0755, true)) {
            return;
        }

        throw new RuntimeException(sprintf('failed to mkdir: %s', F_GIT_OBJECTS_DIR));
    }

    /** @throws RuntimeException */
    public function makeGitHeadsDir(): void
    {
        if (mkdir(F_GIT_HEADS_DIR, 0755, true)) {
            return;
        }

        throw new RuntimeException(sprintf('failed to mkdir: %s', F_GIT_HEADS_DIR));
    }

    /** @throws RuntimeException */
    public function createGitHead(): void
    {
        if (touch(F_GIT_HEAD)) {
            return;
        }

        throw new RuntimeException(sprintf('failed to touch: %s', F_GIT_HEAD));
    }

    /** @throws RuntimeException */
    public function saveGitHead(string $branch): void
    {
        $data = sprintf('ref: %s/%s', GIT_HEADS_DIR, GIT_BASE_BRANCH);

        if (file_put_contents(F_GIT_HEAD, $data, FILE_APPEND) === false) {
            throw new RuntimeException(sprintf('failed to write in file: %s', F_GIT_HEAD));
        }
    }

    /** @throws RuntimeException */
    public function createConfig(): void
    {
        if (!touch(F_GIT_CONFIG)) {
            throw new RuntimeException(sprintf('failed to touch: %s', F_GIT_CONFIG));
        }

        $fp = fopen(F_GIT_CONFIG, "w");
        if ($fp === false) {
            throw new RuntimeException(sprintf('failed to fopen: %s', F_GIT_CONFIG));
        }

        $data = [
            "[core]\n",
            sprintf("\trepositoryformatversion = %d\n", GIT_REPOSITORY_FORMAT_VERSION),
            sprintf("\tfilemode = %s\n", var_export(GIT_FILEMODE, true)),
            sprintf("\tbare = %s\n", var_export(GIT_BARE, true)),
            sprintf("\tlogallrefupdates = %s\n", var_export(GIT_LOG_ALL_REF_UPDATES, true)),
            sprintf("\tignorecase = %s\n", var_export(GIT_IGNORE_CASE, true)),
            sprintf("\tprecomposeunicode = %s\n", var_export(GIT_PRE_COMPOSE_UNICODE, true))
        ];
        foreach ($data as $v) {
            if (fwrite($fp, $v) === false) {
                throw new RuntimeException(sprintf('failed to fwrite: %s', $v));
            }
        }
    }
}
