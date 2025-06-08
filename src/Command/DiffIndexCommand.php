<?php

declare(strict_types=1);

namespace Phpgit\Command;

use Phpgit\Infra\Printer\CliPrinter;
use Phpgit\Infra\Repository\FileRepository;
use Phpgit\Infra\Repository\IndexRepository;
use Phpgit\Infra\Repository\ObjectRepository;
use Phpgit\Infra\Repository\RefRepository;
use Phpgit\Request\DiffIndexRequest;
use Phpgit\Service\ResolveRevisionService;
use Phpgit\Service\TreeToFlatEntriesService;
use Phpgit\UseCase\DiffIndexUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** @see https://git-scm.com/docs/git-diff-index */
#[AsCommand(
    name: 'diff-index',
    description: 'Compare a tree to the working tree or index',
)]
final class DiffIndexCommand extends Command implements CommandInterface
{
    protected function configure(): void
    {
        DiffIndexRequest::setUp($this);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $request = DiffIndexRequest::new($input);

        $printer = new CliPrinter($input, $output);
        $indexRepository = new IndexRepository;
        $objectRepository = new ObjectRepository;
        $fileRepository = new FileRepository;
        $refRepository = new RefRepository;

        $useCase = new DiffIndexUseCase(
            $printer,
            $indexRepository,
            $objectRepository,
            $fileRepository,
            new ResolveRevisionService($refRepository),
            new TreeToFlatEntriesService($objectRepository),
        );

        $result = $useCase($request);

        return $result->value;
    }
}
