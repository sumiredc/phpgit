<?php

declare(strict_types=1);

namespace Phpgit\Command;

use Phpgit\Infra\Printer\CliPrinter;
use Phpgit\Infra\Repository\GitConfigRepository;
use Phpgit\Infra\Repository\ObjectRepository;
use Phpgit\Request\CommitTreeRequest;
use Phpgit\UseCase\CommitTreeUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** @see https://git-scm.com/docs/git-commit-tree */
#[AsCommand(
    name: 'commit-tree',
    description: 'Create a new commit object',
)]
final class CommitTreeCommand extends Command implements CommandInterface
{
    protected function configure(): void
    {
        CommitTreeRequest::setUp($this);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $request = CommitTreeRequest::new($input);

        $printer = new CliPrinter($input, $output);
        $gitConfigRepository = new GitConfigRepository;
        $objectRepository = new ObjectRepository;
        $useCase = new CommitTreeUseCase(
            $printer,
            $gitConfigRepository,
            $objectRepository,
        );

        $result = $useCase($request);

        return $result->value;
    }
}
