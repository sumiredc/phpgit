<?php

namespace Phpgit\UseCase;

use Phpgit\Domain\CommandInput\GitUpdateIndexOptionAction;
use Phpgit\Domain\GitIndex;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Domain\Repository\IndexRepositoryInterface;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Exception\FileNotFoundException;
use Phpgit\Service\FileToHashService;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Throwable;

final class GitUpdateIndexUseCase
{
    public function __construct(
        private readonly StyleInterface&OutputInterface $io,
        private readonly LoggerInterface $logger,
        private readonly ObjectRepositoryInterface $objectRepository,
        private readonly FileRepositoryInterface $fileRepository,
        private readonly IndexRepositoryInterface $indexRepository,
    ) {}

    public function __invoke(GitUpdateIndexOptionAction $action, string $file): Result
    {
        try {
            return match ($action) {
                GitUpdateIndexOptionAction::Add => $this->actionAdd($file),
                GitUpdateIndexOptionAction::Remove => $this->actionRemove(),
                GitUpdateIndexOptionAction::ForceRemove => $this->actionForceRemove(),
                GitUpdateIndexOptionAction::Replace => $this->actionReplace(),
            };
        } catch (FileNotFoundException) {
            $this->io->error(sprintf('file not found: %s', $file));

            return Result::Invalid;
        } catch (Throwable $th) {
            $this->logger->error('failed to update index', ['exception' => $th]);

            return Result::Failure;
        }
    }

    private function actionAdd(string $file): Result
    {
        $fileToHashService = new FileToHashService($this->fileRepository);
        [$trackingFile, $gitObject, $objectHash] = $fileToHashService($file);

        if (!$this->objectRepository->exists($objectHash)) {
            $this->objectRepository->save($gitObject);
        }

        $fileStat = $this->fileRepository->getStat($trackingFile);
        if (is_null($fileStat)) {
            throw new RuntimeException('failed to get stat');
        }

        $gitIndex = (function () {
            if ($this->indexRepository->exists()) {
                return $this->indexRepository->get();
            }

            if (!$this->indexRepository->createEmpty()) {
                throw new RuntimeException('failed to create index');
            }

            return GitIndex::make();
        })();

        $indexEntry = IndexEntry::make($fileStat, $objectHash, $trackingFile);
        $gitIndex->addEntry($indexEntry);

        $this->indexRepository->save($gitIndex);

        return Result::Success;
    }

    private function actionRemove(): Result
    {
        return Result::Success;
    }

    private function actionForceRemove(): Result
    {
        return Result::Success;
    }

    private function actionReplace(): Result
    {
        return Result::Success;
    }
}
