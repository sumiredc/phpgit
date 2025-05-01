<?php

declare(strict_types=1);

namespace Phpgit\Repository;

use ParseError;
use Phpgit\Domain\GitConfig;
use Phpgit\Domain\Repository\GitConfigRepositoryInterface;
use RuntimeException;

readonly final class GitConfigRepository implements GitConfigRepositoryInterface
{
    /** @throws RuntimeException */
    public function get(): GitConfig
    {
        $config = parse_ini_file(GIT_CONFIG, true);
        if ($config === false) {
            throw new RuntimeException(sprintf('failed to load ini: %s', GIT_CONFIG));
        }

        return GitConfig::new(
            $config['core']['repositoryformatversion'] ?? throw new ParseError('failed to parse to core.repositoryformatversion'),
            $config['core']['filemode'] ?? throw new ParseError('failed to parse to core.filemode'),
            $config['core']['bare'] ?? throw new ParseError('failed to parse to core.bare'),
            $config['core']['logallrefupdates'] ?? throw new ParseError('failed to parse to core.logallrefupdates'),
            $config['core']['ignorecase'] ?? throw new ParseError('failed to parse to core.ignorecase'),
            $config['core']['precomposeunicode'] ?? throw new ParseError('failed to parse to core.precomposeunicode'),
            $config['user']['name'] ?? GIT_DEFAULT_USER_NAME,
            $config['user']['email'] ?? GIT_DEFAULT_USER_EMAIL,
        );
    }

    /** @throws RuntimeException */
    public function create(): void
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
