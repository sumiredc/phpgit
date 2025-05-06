<?php

declare(strict_types=1);

namespace Phpgit\Domain\Repository;

use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Reference;

interface RefRepositoryInterface
{
    public function exists(Reference $ref): bool;

    /** 
     * @throws FileAlreadyExistsException
     * @throws RuntimeException
     */
    public function create(Reference $ref, ObjectHash $hash): void;

    /**
     * @throws FileNotFoundException
     * @throws RuntimeException
     */
    public function update(Reference $ref, ObjectHash $hash): void;

    /**
     * @throws RuntimeException
     */
    public function head(): ?Reference;

    /**
     * @throws RuntimeException
     */
    public function resolve(Reference $ref): ObjectHash;
}
