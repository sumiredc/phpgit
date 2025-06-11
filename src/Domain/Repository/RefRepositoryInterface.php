<?php

declare(strict_types=1);

namespace Phpgit\Domain\Repository;

use Phpgit\Domain\HeadType;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Reference;
use RuntimeException;

interface RefRepositoryInterface
{
    public function exists(Reference $ref): bool;

    /** 
     * @throws RuntimeException
     */
    public function create(Reference $ref, ObjectHash $hash): void;

    /**
     * @throws RuntimeException
     */
    public function update(Reference $ref, ObjectHash $hash): void;

    /**
     * @throws RuntimeException
     */
    public function updateHead(ObjectHash $hash): void;

    /**
     * @throws RuntimeException
     */
    public function createOrUpdate(Reference $ref, ObjectHash $hash): void;

    /**
     * @throws RuntimeException
     */
    public function delete(Reference $ref): void;

    /**
     * @throws RuntimeException
     */
    public function resolve(Reference $ref): ObjectHash;

    public function headType(): HeadType;

    /**
     * @throws RuntimeException
     */
    public function head(): ?Reference;

    /**
     * @throws RuntimeException
     */
    public function resolveHead(): ?ObjectHash;

    public function dereference(string $value): ?Reference;
}
