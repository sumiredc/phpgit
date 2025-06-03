<?php

declare(strict_types=1);

namespace Phpgit\Command;

use Phpgit\Infra\Printer\CliPrinter;
use Phpgit\Infra\Repository\IndexRepository;
use Phpgit\Infra\Repository\ObjectRepository;
use Phpgit\Service\CreateSegmentTreeService;
use Phpgit\Service\SaveTreeObjectService;
use Phpgit\UseCase\WriteTreeUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** @see https://git-scm.com/docs/git-write-tree */
#[AsCommand(
    name: 'write-tree',
    description: 'Create a tree object from the current index',
)]
final class WriteTreeCommand extends Command implements CommandInterface
{
    protected function configure(): void {}

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $printer = new CliPrinter($input, $output);
        $indexRepository = new IndexRepository;
        $objectRepository = new ObjectRepository;

        $useCase = new WriteTreeUseCase(
            $printer,
            $indexRepository,
            new CreateSegmentTreeService($objectRepository),
            new SaveTreeObjectService($objectRepository)
        );

        $result = $useCase();

        return $result->value;
    }
}
