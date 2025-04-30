<?php

declare(strict_types=1);

namespace Phpgit\Domain\Repository;

use Phpgit\Domain\CompressedPayload;
use Phpgit\Domain\GitObject;
use Phpgit\Domain\ObjectHash;
use RuntimeException;

interface ObjectRepositoryInterface
{
    /** @throws RuntimeException */
    public function save(GitObject $gitObject): ObjectHash;

    /** @throws RuntimeException */
    public function getCompressedPayload(ObjectHash $objectHash): CompressedPayload;

    /** @throws RuntimeException */
    public function get(ObjectHash $objectHash): GitObject;

    public function exists(ObjectHash $objectHash): bool;
}
