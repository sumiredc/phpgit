<?php

declare(strict_types=1);

namespace Phpgit\Command;

use Phpgit\Infra\Printer\CliPrinter;
use Phpgit\Infra\Repository\ObjectRepository;
use Phpgit\Request\CatFileRequest;
use Phpgit\UseCase\CatFileUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** @see https://git-scm.com/docs/git-cat-file */
#[AsCommand(
    name: 'cat-file',
    description: 'Provide contents or details of repository objects',
)]
final class CatFileCommand extends Command implements CommandInterface
{
    protected function configure(): void
    {
        CatFileRequest::setUp($this);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $request = CatFileRequest::new($input);

        $printer = new CliPrinter($input, $output);
        $objectRepository = new ObjectRepository();
        $useCase = new CatFileUseCase($printer, $objectRepository);

        $result = $useCase($request);

        return $result->value;
    }
}
