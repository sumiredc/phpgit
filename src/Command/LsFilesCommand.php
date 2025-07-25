<?php

declare(strict_types=1);

namespace Phpgit\Command;

use Phpgit\Infra\Printer\CliPrinter;
use Phpgit\Infra\Repository\IndexRepository;
use Phpgit\Request\LsFilesRequest;
use Phpgit\UseCase\LsFilesUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** @see https://git-scm.com/docs/git-ls-files */
#[AsCommand(
    name: 'ls-files',
    description: 'Show information about files in the index and the working tree',
)]
final class LsFilesCommand extends Command implements CommandInterface
{
    protected function configure(): void
    {
        LsFilesRequest::setUp($this);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $request = LsFilesRequest::new($input);

        $printer = new CliPrinter($input, $output);
        $indexRepository = new IndexRepository();
        $useCase = new LsFilesUseCase($printer, $indexRepository);

        $result = $useCase($request);

        return $result->value;
    }
}
