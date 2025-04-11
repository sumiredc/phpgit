<?php

namespace Phpgit\Domain\Repository;

use Phpgit\Domain\GitObject;
use Phpgit\Domain\ObjectHash;
use RuntimeException;

interface ObjectRepositoryInterface
{
    /** @throws RuntimeException */
    public function save(GitObject $gitObject): ObjectHash;

    /** @throws RuntimeException */
    public function getCompressed(ObjectHash $objectHash): string;

    /** @throws RuntimeException */
    public function get(ObjectHash $objectHash): GitObject;

    public function exists(ObjectHash $objectHash): bool;
}
