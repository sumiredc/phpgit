<?php

declare(strict_types=1);

namespace Phpgit\Command;

use Phpgit\Lib\IO;
use Phpgit\Repository\GitResourceRepository;
use Phpgit\UseCase\GitInitUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** @see https://git-scm.com/docs/git-init */
#[AsCommand(
    name: 'git:init',
    description: 'Create an empty Git repository or reinitialize an existing one',
)]
final class GitInitCommand extends Command
{
    protected function configure(): void {}

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new IO($input, $output);
        $gitResourceRepository = new GitResourceRepository();
        $useCase = new GitInitUseCase($io, $gitResourceRepository);

        $result = $useCase();

        return $result->value;
    }
}
