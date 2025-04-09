<?php

namespace Phpgit\UseCase;

use Phpgit\Domain\Branch;
use Phpgit\Domain\GitPath;
use Phpgit\Domain\Result;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;

final class GitInitUseCase
{
    public function __construct(
        private readonly StyleInterface&OutputInterface $io,
        private readonly LoggerInterface $logger,
        private readonly GitPath $gitPath,
    ) {}

    public function __invoke(): Result
    {
        if (is_dir($this->gitPath->gitDir)) {
            $this->io->warning('a Git repository already exists in this directory.');
            return Result::Invalid;
        }

        if ($this->setUpFiles($this->gitPath)) {
            $this->io->success(sprintf('Initialized empty Git repository in %s', $this->gitPath->gitDir));
            return Result::Success;
        }

        $this->io->error('unable to initialize git repository in the current directory.');

        return Result::Failure;
    }

    private function setUpFiles(GitPath $gitPath): bool
    {
        if (!mkdir($gitPath->objectsDir, 0755, true)) {
            $this->logger->error(sprintf('failed to mkdir: %s', $gitPath->objectsDir));
            return false;
        }

        if (!mkdir($gitPath->headsDir, 0755, true)) {
            $this->logger->error(sprintf('failed to mkdir: %s', $gitPath->headsDir));
            return false;
        }

        if (!touch($gitPath->head)) {
            $this->logger->error(sprintf('failed to touch: %s', $gitPath->head));
            return false;
        }

        if (!$this->setCurrentBranch($gitPath)) {
            $this->logger->error(sprintf('failed to write in file: %s', $gitPath->head));
        }

        return true;
    }

    private function setCurrentBranch(GitPath $gitPath): bool
    {
        $data = sprintf('ref: %s/%s', GitPath::HEADS_DIR, Branch::BASE);
        if (file_put_contents($gitPath->head, $data, FILE_APPEND) === false) {
            return false;
        }

        return true;
    }
}
