<?php

declare(strict_types=1);

namespace Phpgit\Command;

use Phpgit\Infra\Printer\CliPrinter;
use Phpgit\Infra\Repository\IndexRepository;
use Phpgit\Infra\Repository\ObjectRepository;
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
        $printer = new CliPrinter($input, $output);
        $indexRepository = new IndexRepository;
        $objectRepository = new ObjectRepository;
        $useCase = new GitWriteTreeUseCase($printer, $indexRepository, $objectRepository);

        $result = $useCase();

        return $result->value;
    }
}
