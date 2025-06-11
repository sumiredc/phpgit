<?php

declare(strict_types=1);

namespace Phpgit\Service;

use Phpgit\Domain\CommitObject;
use Phpgit\Domain\ObjectHash;

interface CreateCommitTreeServiceInterface
{
    public function __invoke(ObjectHash $treetHash, string $message, ?ObjectHash $parentHash): CommitObject;
}
