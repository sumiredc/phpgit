<?php

namespace Phpgit\UseCase;

use Phpgit\Domain\Branch;
use Phpgit\Domain\Result;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;

final class GitInitUseCase
{
    public function __construct(
        private readonly StyleInterface&OutputInterface $io,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(): Result
    {
        if (is_dir(F_GIT_DIR)) {
            $this->io->warning('a Git repository already exists in this directory.');
            return Result::Invalid;
        }

        if ($this->setUpFiles()) {
            $this->io->success(sprintf('Initialized empty Git repository in %s', F_GIT_DIR));
            return Result::Success;
        }

        $this->io->error('unable to initialize git repository in the current directory.');

        return Result::Failure;
    }

    private function setUpFiles(): bool
    {
        if (!mkdir(F_GIT_OBJECTS_DIR, 0755, true)) {
            $this->logger->error(sprintf('failed to mkdir: %s', F_GIT_OBJECTS_DIR));
            return false;
        }

        if (!mkdir(F_GIT_HEADS_DIR, 0755, true)) {
            $this->logger->error(sprintf('failed to mkdir: %s', F_GIT_HEADS_DIR));
            return false;
        }

        if (!touch(F_GIT_HEAD)) {
            $this->logger->error(sprintf('failed to touch: %s', F_GIT_HEAD));
            return false;
        }

        $data = sprintf('ref: %s/%s', F_GIT_HEADS_DIR, Branch::BASE);
        if (file_put_contents(F_GIT_HEAD, $data, FILE_APPEND) === false) {
            $this->logger->error(sprintf('failed to write in file: %s', F_GIT_HEAD));
        }

        return true;
    }
}
