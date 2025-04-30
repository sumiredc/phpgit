<?php

declare(strict_types=1);

namespace Phpgit\Command;

use Phpgit\Lib\IO;
use Phpgit\Repository\IndexRepository;
use Phpgit\Repository\ObjectRepository;
use Phpgit\UseCase\GitWriteTreeUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** @see https://git-scm.com/docs/git-write-tree */
#[AsCommand(
    name: 'git:write-tree',
    description: 'Create a tree object from the current index',
)]
final class GitWriteTreeCommand extends Command
{
    protected function configure(): void {}

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new IO($input, $output);
        $indexRepository = new IndexRepository;
        $objectRepository = new ObjectRepository;
        $useCase = new GitWriteTreeUseCase($io, $indexRepository, $objectRepository);

        $result = $useCase();

        return $result->value;
    }
}
