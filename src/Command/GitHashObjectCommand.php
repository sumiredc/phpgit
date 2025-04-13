<?php

declare(strict_types=1);

namespace Phpgit\Command;

use Phpgit\Domain\Result;
use Phpgit\Lib\IO;
use Phpgit\Repository\FileRepository;
use Phpgit\Repository\ObjectRepository;
use Phpgit\UseCase\GitHashObjectUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** @see https://git-scm.com/docs/git-hash-object */
#[AsCommand(
    name: 'git:hash-object',
    description: '',
)]
final class GitHashObjectCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument('file', InputArgument::REQUIRED, 'The file to hash');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = strval($input->getArgument('file'));

        $io = new IO($input, $output);
        $objectRepository = new ObjectRepository();
        $fileRepository = new FileRepository();
        $useCase = new GitHashObjectUseCase($io, $objectRepository, $fileRepository);

        $result = $useCase($file);

        return $result->value;
    }
}
