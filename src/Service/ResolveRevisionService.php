<?php

declare(strict_types=1);

namespace Phpgit\Service;

use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Reference;
use Phpgit\Domain\Repository\RefRepositoryInterface;

readonly final class ResolveRevisionService implements ResolveRevisionServiceInterface
{
    public function __construct(
        private readonly RefRepositoryInterface $refRepository
    ) {}

    public function __invoke(string $rev): ?ObjectHash
    {
        $object = ObjectHash::tryParse($rev);
        if (!is_null($object)) {
            return $object;
        }

        if ($rev === GIT_HEAD) {
            return $this->refRepository->resolveHead();
        }

        $ref = Reference::tryParse($rev);
        if ($ref && $this->refRepository->exists($ref)) {
            return $this->refRepository->resolve($ref);
        }

        return null;
    }
}
