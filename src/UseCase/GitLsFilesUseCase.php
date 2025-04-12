<?php

declare(strict_types=1);

namespace Phpgit\UseCase;

use Phpgit\Domain\CommandInput\GitLsFileOptionAction;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\Repository\IndexRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Lib\IOInterface;
use Throwable;

final class GitLsFilesUseCase
{
    public function __construct(
        private readonly IOInterface $io,
        private readonly IndexRepositoryInterface $indexRepository,
    ) {}

    public function __invoke(GitLsFileOptionAction $action): Result
    {
        if (!$this->indexRepository->exists()) {
            return Result::Success;
        }

        try {
            return match ($action) {
                GitLsFileOptionAction::Default => $this->actionDefault(),
                GitLsFileOptionAction::Stage => $this->actionStage(),
                GitLsFileOptionAction::Debug => $this->actionDebug(),
            };
        } catch (Throwable $th) {
            $this->io->stackTrace($th);

            return Result::Failure;
        }
    }

    public function actionDefault(): Result
    {
        $gitIndex = $this->indexRepository->get();
        $list = array_map(
            fn(IndexEntry $indexEntry) => $indexEntry->trackingFile->path,
            $gitIndex->entries()
        );

        $this->io->writeln($list);

        return Result::Success;
    }

    public function actionStage(): Result
    {
        $gitIndex = $this->indexRepository->get();
        $list = array_map(
            fn(IndexEntry $indexEntry) => sprintf(
                "%o %s %-7s %s",
                $indexEntry->mode,
                $indexEntry->objectHash->value(),
                $indexEntry->stage,
                $indexEntry->trackingFile->path,
            ),
            $gitIndex->entries()
        );

        $this->io->writeln($list);

        return Result::Success;
    }

    public function actionDebug(): Result
    {
        return Result::Success;
    }
}
