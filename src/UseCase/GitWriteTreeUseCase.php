<?php

declare(strict_types=1);

namespace Phpgit\UseCase;

use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Repository\IndexRepositoryInterface;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Exception\InvalidObjectException;
use Phpgit\Lib\IOInterface;
use Phpgit\Service\CreateSegmentTreeService;
use Phpgit\Service\SaveTreeObjectService;
use Throwable;

final class GitWriteTreeUseCase
{
    public function __construct(
        private readonly IOInterface $io,
        private readonly IndexRepositoryInterface $indexRepository,
        private readonly ObjectRepositoryInterface $objectRepository,
    ) {}

    public function __invoke(): Result
    {
        try {
            $objectHash = $this->createTree();
            $this->io->writeln($objectHash->value);

            return Result::Success;
        } catch (InvalidObjectException $ex) {
            $this->io->writeln([
                $ex->getMessage(),
                'fatal: git-write-tree: error building trees'
            ]);

            return Result::GitError;
        } catch (Throwable $th) {
            $this->io->stackTrace($th);

            return Result::InternalError;
        }
    }

    private function createTree(): ObjectHash
    {
        $gitIndex = $this->indexRepository->getOrCreate();

        $segmentTreeService = new CreateSegmentTreeService($this->objectRepository);
        $segmentTree = $segmentTreeService($gitIndex);

        $treeObjectService = new SaveTreeObjectService($this->objectRepository);

        return $treeObjectService($segmentTree);
    }
}
