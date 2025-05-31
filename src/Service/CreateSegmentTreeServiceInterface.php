<?php

declare(strict_types=1);

namespace Phpgit\Service;

use Phpgit\Domain\GitIndex;
use Phpgit\Domain\SegmentTree;
use Phpgit\Exception\InvalidObjectException;
use RuntimeException;

interface CreateSegmentTreeServiceInterface
{
    /**
     * @throws InvalidObjectException
     * @throws RuntimeException
     */
    public function __invoke(GitIndex $gitIndex): SegmentTree;
}
