<?php

declare(strict_types=1);

namespace Phpgit\UseCase;

use Phpgit\Domain\Repository\GitConfigRepositoryInterface;
use Phpgit\Domain\Repository\GitResourceRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Lib\IOInterface;
use Throwable;

final class GitInitUseCase
{
    public function __construct(
        private readonly IOInterface $io,
        private readonly GitResourceRepositoryInterface $gitResourceRepository,
        private readonly GitConfigRepositoryInterface $gitConfigRepository,
    ) {}

    public function __invoke(): Result
    {
        if ($this->gitResourceRepository->existsGitDir()) {
            // TODO: reinitialzed は未実装
            $this->io->writeln(sprintf('Reinitialized existing Git repository in %s/', F_GIT_DIR));
            return Result::Success;
        }

        try {
            $this->gitResourceRepository->makeGitObjectDir();
            $this->gitResourceRepository->makeGitHeadsDir();
            $this->gitResourceRepository->createGitHead();
            $this->gitResourceRepository->saveGitHead(GIT_BASE_BRANCH);
            $this->gitConfigRepository->create();

            $this->io->writeln(sprintf('Initialized empty Git repository in %s', F_GIT_DIR));

            return Result::Success;
        } catch (Throwable $th) {
            $this->io->stackTrace($th);

            return Result::InternalError;
        }
    }
}
