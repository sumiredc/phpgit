<?php

declare(strict_types=1);

namespace Phpgit\Command;

use Phpgit\Infra\Printer\CliPrinter;
use Phpgit\Infra\Repository\FileRepository;
use Phpgit\Infra\Repository\ObjectRepository;
use Phpgit\Infra\Repository\RefRepository;
use Phpgit\Request\RevParseRequest;
use Phpgit\UseCase\RevParseUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** @see https://git-scm.com/docs/rev-parse */
#[AsCommand(
    name: 'rev-parse',
    description: 'Pick out and massage parameters',
)]
final class RevParseCommand extends Command implements CommandInterface
{
    protected function configure(): void
    {
        $this->addArgument('args', InputArgument::IS_ARRAY, 'Separated by space');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $request = RevParseRequest::new($input);

        $printer = new CliPrinter($input, $output);
        $fileRepository = new FileRepository();
        $objectRepository = new ObjectRepository();
        $refRepository = new RefRepository();
        $useCase = new RevParseUseCase($printer, $fileRepository, $objectRepository, $refRepository);

        $result = $useCase($request);

        return $result->value;
    }
}
