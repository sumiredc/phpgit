<?php

declare(strict_types=1);

namespace Phpgit\Command;

use Phpgit\Helper\DiffIndexHelper;
use Phpgit\Infra\Printer\CliPrinter;
use Phpgit\Infra\Repository\FileRepository;
use Phpgit\Infra\Repository\GitConfigRepository;
use Phpgit\Infra\Repository\IndexRepository;
use Phpgit\Infra\Repository\ObjectRepository;
use Phpgit\Infra\Repository\RefRepository;
use Phpgit\Request\CommitRequest;
use Phpgit\Service\CreateCommitTreeService;
use Phpgit\Service\CreateSegmentTreeService;
use Phpgit\Service\ResolveRevisionService;
use Phpgit\Service\SaveTreeObjectService;
use Phpgit\Service\TreeToFlatEntriesService;
use Phpgit\UseCase\CommitUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** @see https://git-scm.com/docs/git-commit */
#[AsCommand(
    name: 'commit',
    description: 'Create a new commit object',
)]
final class CommitCommand extends Command implements CommandInterface
{
    protected function configure(): void
    {
        CommitRequest::setUp($this);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $request = CommitRequest::new($input);

        $printer = new CliPrinter($input, $output);
        $gitConfigRepository = new GitConfigRepository;
        $indexRepository = new IndexRepository;
        $refRepository = new RefRepository;
        $objectRepository = new ObjectRepository;
        $fileRepository = new FileRepository;

        $useCase = new CommitUseCase(
            $printer,
            $indexRepository,
            $refRepository,
            $objectRepository,
            new ResolveRevisionService($refRepository),
            new TreeToFlatEntriesService($objectRepository),
            new CreateSegmentTreeService($objectRepository),
            new SaveTreeObjectService($objectRepository),
            new CreateCommitTreeService($gitConfigRepository),
            new DiffIndexHelper($fileRepository, $objectRepository),
        );

        $result = $useCase($request);

        return $result->value;
    }
}
