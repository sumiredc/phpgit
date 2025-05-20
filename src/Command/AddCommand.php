<?php

declare(strict_types=1);

namespace Phpgit\Command;

use Phpgit\Infra\Printer\CliPrinter;
use Phpgit\Infra\Repository\FileRepository;
use Phpgit\Infra\Repository\IndexRepository;
use Phpgit\Infra\Repository\ObjectRepository;
use Phpgit\Request\AddRequest;
use Phpgit\UseCase\AddUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** @see https://git-scm.com/docs/git-add */
#[AsCommand(
    name: 'add',
    description: 'Add file contents to the index',
)]
final class AddCommand extends Command implements CommandInterface
{
    protected function configure(): void
    {
        AddRequest::setUp($this);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $request = AddRequest::new($input);

        $printer = new CliPrinter($input, $output);
        $fileRepository = new FileRepository;
        $objectRepository = new ObjectRepository;
        $indexRepository = new IndexRepository;

        $useCase = new AddUseCase(
            $printer,
            $fileRepository,
            $objectRepository,
            $indexRepository,
        );

        $result = $useCase($request);

        return $result->value;
    }
}
