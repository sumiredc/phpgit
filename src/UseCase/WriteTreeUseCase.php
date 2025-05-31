<?php

declare(strict_types=1);

namespace Phpgit\UseCase;

use Phpgit\Domain\Repository\IndexRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Exception\InvalidObjectException;
use Phpgit\Domain\Printer\PrinterInterface;
use Phpgit\Service\CreateSegmentTreeServiceInterface;
use Phpgit\Service\SaveTreeObjectServiceInterface;
use Throwable;

final class WriteTreeUseCase
{
    public function __construct(
        private readonly PrinterInterface $printer,
        private readonly IndexRepositoryInterface $indexRepository,
        private readonly CreateSegmentTreeServiceInterface $createSegmentTreeService,
        private readonly SaveTreeObjectServiceInterface $saveTreeObjectService,
    ) {}

    public function __invoke(): Result
    {
        try {
            $gitIndex = $this->indexRepository->getOrCreate();

            $segmentTree = ($this->createSegmentTreeService)($gitIndex);
            $objectHash = ($this->saveTreeObjectService)($segmentTree);

            $this->printer->writeln($objectHash->value);

            return Result::Success;
        } catch (InvalidObjectException $ex) {
            $this->printer->writeln([
                $ex->getMessage(),
                'fatal: git-write-tree: error building trees'
            ]);

            return Result::GitError;
        } catch (Throwable $th) {
            $this->printer->stackTrace($th);

            return Result::InternalError;
        }
    }
}
