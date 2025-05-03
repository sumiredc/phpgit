<?php

declare(strict_types=1);

namespace Phpgit\Command;

use Phpgit\Lib\IO;
use Phpgit\Repository\GitConfigRepository;
use Phpgit\Repository\ObjectRepository;
use Phpgit\UseCase\GitCommitTreeUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/** @see https://git-scm.com/docs/git-write-tree */
#[AsCommand(
    name: 'git:commit-tree',
    description: 'Create a new commit object',
)]
final class GitCommitTreeCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('tree', InputArgument::REQUIRED, 'An existing tree object.')
            ->addOption('message', '-m', InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new IO($input, $output);
        $gitConfigRepository = new GitConfigRepository;
        $objectRepository = new ObjectRepository;
        $useCase = new GitCommitTreeUseCase($io, $gitConfigRepository, $objectRepository);

        $object = $input->getArgument('tree');
        $message = $input->getOption('message');
        $result = $useCase($object, $message);

        return $result->value;
    }
}
