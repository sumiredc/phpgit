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
}
