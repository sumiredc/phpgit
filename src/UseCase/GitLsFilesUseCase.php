<?php

declare(strict_types=1);

namespace Phpgit\UseCase;

use Phpgit\Domain\CommandInput\GitLsFilesOptionAction;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\Repository\IndexRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Domain\Printer\PrinterInterface;
use Phpgit\Request\GitLsFilesRequest;
use Throwable;

final class GitLsFilesUseCase
{
    public function __construct(
        private readonly PrinterInterface $printer,
        private readonly IndexRepositoryInterface $indexRepository,
    ) {}

    public function __invoke(GitLsFilesRequest $request): Result
    {
        if (!$this->indexRepository->exists()) {
            return Result::Success;
        }

        try {
            return match ($request->action) {
                GitLsFilesOptionAction::Default => $this->actionDefault(),
                GitLsFilesOptionAction::Tag => $this->actionTag(),
                GitLsFilesOptionAction::Zero => $this->actionZero(),
                GitLsFilesOptionAction::Stage => $this->actionStage(),
                GitLsFilesOptionAction::Debug => $this->actionDebug(),
            };
        } catch (Throwable $th) {
            $this->printer->stackTrace($th);

            return Result::InternalError;
        }
    }

    public function actionDefault(): Result
    {
        $gitIndex = $this->indexRepository->get();
        $list = array_map(
            fn(IndexEntry $indexEntry) => $indexEntry->trackingFile->path,
            $gitIndex->entries
        );

        $this->printer->writeln($list);

        return Result::Success;
    }

    public function actionTag(): Result
    {
        $gitIndex = $this->indexRepository->get();
        $list = array_map(
            fn(IndexEntry $indexEntry) => sprintf(
                '%s %s',
                'H', // TODO: 一旦 Hash object で固定 https://git-scm.com/docs/git-ls-files#Documentation/git-ls-files.txt--t
                $indexEntry->trackingFile->path
            ),
            $gitIndex->entries
        );

        $this->printer->writeln($list);

        return Result::Success;
    }

    public function actionZero(): Result
    {
        $gitIndex = $this->indexRepository->get();
        $line = array_reduce(
            $gitIndex->entries,
            fn(string $carry, IndexEntry $indexEntry) => sprintf("%s%s\0", $carry, $indexEntry->trackingFile->path),
            '',
        );

        $this->printer->echo($line);

        return Result::Success;
    }

    public function actionStage(): Result
    {
        $gitIndex = $this->indexRepository->get();
        $list = array_map(
            fn(IndexEntry $indexEntry) => sprintf(
                "%s %s %d\t%s",
                $indexEntry->gitFileMode->value,
                $indexEntry->objectHash->value,
                $indexEntry->stage,
                $indexEntry->trackingFile->path,
            ),
            $gitIndex->entries
        );

        $this->printer->writeln($list);

        return Result::Success;
    }

    public function actionDebug(): Result
    {
        $gitIndex = $this->indexRepository->get();
        foreach ($gitIndex->entries as $indexEntry) {
            $entry = [
                $indexEntry->trackingFile->path,
                sprintf("  ctime: %d:%d", $indexEntry->ctime, $indexEntry->ctimeNano),
                sprintf("  mtime: %d:%d", $indexEntry->mtime, $indexEntry->mtimeNano),
                sprintf("  dev: %d\tino: %d", $indexEntry->dev, $indexEntry->ino),
                sprintf("  uid: %d\tgid: %d", $indexEntry->uid, $indexEntry->gid),
                sprintf("  size: %d\tflags: %d", $indexEntry->size, $indexEntry->flags())
            ];

            $this->printer->writeln($entry);
        }

        return Result::Success;
    }
}
