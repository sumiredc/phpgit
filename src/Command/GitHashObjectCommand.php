<?php

declare(strict_types=1);

namespace Phpgit\Command;

use Phpgit\Infra\Printer\CliPrinter;
use Phpgit\Infra\Repository\FileRepository;
use Phpgit\Request\GitHashObjectRequest;
use Phpgit\UseCase\GitHashObjectUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** @see https://git-scm.com/docs/git-hash-object */
#[AsCommand(
    name: 'git:hash-object',
    description: 'Compute object ID and optionally create an object from a file',
)]
final class GitHashObjectCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument('file', InputArgument::REQUIRED, 'The file to hash');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $request = GitHashObjectRequest::new($input);

        $printer = new CliPrinter($input, $output);
        $fileRepository = new FileRepository();
        $useCase = new GitHashObjectUseCase($printer, $fileRepository);

        $result = $useCase($request);

        return $result->value;
    }
}
