<?php

namespace Phpgit\Domain\Repository;

use Phpgit\Domain\GitObject;
use Phpgit\Domain\ObjectHash;
use RuntimeException;

interface ObjectRepositoryInterface
{
    /** @throws RuntimeException */
    public function saveObject(string $object): ObjectHash;

    /** @throws RuntimeException */
    public function getCompressObject(ObjectHash $objectHash): string;

    /** @throws RuntimeException */
    public function getObject(ObjectHash $objectHash): GitObject;

    public function existObject(ObjectHash $objectHash): bool;
}
