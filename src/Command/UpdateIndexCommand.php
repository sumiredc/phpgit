<?php

declare(strict_types=1);

namespace Phpgit\Command;

use Phpgit\Infra\Printer\CliPrinter;
use Phpgit\Infra\Repository\FileRepository;
use Phpgit\Infra\Repository\IndexRepository;
use Phpgit\Infra\Repository\ObjectRepository;
use Phpgit\Request\UpdateIndexRequest;
use Phpgit\UseCase\UpdateIndexUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** @see https://git-scm.com/docs/git-update-index */
#[AsCommand(
    name: 'update-index',
    description: 'Register file contents in the working tree to the index',
)]
final class UpdateIndexCommand extends Command implements CommandInterface
{
    protected function configure(): void
    {
        UpdateIndexRequest::setUp($this);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $request = UpdateIndexRequest::new($input);

        $printer = new CliPrinter($input, $output);
        $objectRepository = new ObjectRepository;
        $fileRepository = new FileRepository;
        $indexRepository = new IndexRepository;
        $useCase = new UpdateIndexUseCase(
            $printer,
            $objectRepository,
            $fileRepository,
            $indexRepository
        );

        $result = $useCase($request);

        return $result->value;
    }
}
