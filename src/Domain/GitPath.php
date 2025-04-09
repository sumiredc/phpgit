<?php

namespace Phpgit\Domain;

/**
 * Constant: git-relative path (ex: refs/heads)
 * Property: absolute path (ex: /{project-path}/{gitdir}/refs/heads)
 */
readonly final class GitPath
{
    public const TRACKING_ROOT = 'project';
    public const GIT_DIR = '.phpgit';
    public const OBJECTS_DIR = 'objects';
    public const HEADS_DIR = 'refs/heads';
    public const HEAD = 'HEAD';

    public readonly string $trackingRoot;
    public readonly string $gitDir;
    public readonly string $objectsDir;
    public readonly string $headsDir;
    public readonly string $head;

    public function __construct()
    {
        $this->trackingRoot = sprintf("%s/%s", getcwd(), self::TRACKING_ROOT);
        $this->gitDir = sprintf('%s/%s', $this->trackingRoot, self::GIT_DIR);
        $this->objectsDir = sprintf('%s/%s', $this->gitDir, self::OBJECTS_DIR);
        $this->headsDir = sprintf('%s/%s', $this->gitDir, self::HEADS_DIR);
        $this->head = sprintf('%s/%s', $this->gitDir, self::HEAD);
    }
}
