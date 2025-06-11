<?php

declare(strict_types=1);

namespace Phpgit\Service;

use LogicException;
use Phpgit\Domain\HeadType;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Reference;
use Phpgit\Domain\Repository\RefRepositoryInterface;
use UnhandledMatchError;

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
            return $this->parseHead();
        }

        $ref = Reference::tryParse($rev);
        if (!is_null($ref) && $this->refRepository->exists($ref)) {
            return $this->refRepository->resolve($ref);
        }

        return null;
    }

    private function parseHead(): ?ObjectHash
    {
        $headType = $this->refRepository->headType();

        switch ($headType) {
            case HeadType::Hash:
                return $this->refRepository->resolveHead();

                break;

            case HeadType::Reference:
                $ref = $this->refRepository->head();
                if (is_null($ref) || !$this->refRepository->exists($ref)) {
                    return null;
                };

                return $this->refRepository->resolve($ref);

            case HeadType::Unknown:
                throw new LogicException('HEAD is Unknown');

            default:
                throw new UnhandledMatchError(sprintf('Unhandled enum case: %s', $headType->name)); // @codeCoverageIgnore
        }
    }
}
