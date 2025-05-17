<?php

declare(strict_types=1);

namespace Phpgit\Command;

use Phpgit\Infra\Printer\CliPrinter;
use Phpgit\Infra\Repository\ObjectRepository;
use Phpgit\Infra\Repository\RefRepository;
use Phpgit\Request\GitUpdateRefRequest;
use Phpgit\UseCase\GitUpdateRefUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** @see https://git-scm.com/docs/git-update-ref */
#[AsCommand(
    name: 'git:update-ref',
    description: 'Update the object name stored in a ref safely',
)]
final class GitUpdateRefCommand extends Command implements CommandInterface
{
    protected function configure(): void
    {
        GitUpdateRefRequest::setUp($this);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $request = GitUpdateRefRequest::new($input);
        $printer = new CliPrinter($input, $output);
        $objectRepository = new ObjectRepository;
        $refRepository = new RefRepository;
        $useCase = new GitUpdateRefUseCase($printer, $objectRepository, $refRepository);

        $result = $useCase($request);

        return $result->value;
    }
}
