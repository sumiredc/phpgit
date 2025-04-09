<?php

namespace Phpgit\Command;

use Phpgit\Domain\GitPath;
use Phpgit\Domain\Result;
use Phpgit\Lib\Logger;
use Phpgit\Repository\ObjectRepository;
use Phpgit\UseCase\GitHashObjectUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'git:hash-object',
    description: '指定したファイルの blob オブジェクトを作成します',
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

        $logger = Logger::console();
        $io = new SymfonyStyle($input, $output);
        $gitPath = new GitPath;
        $objectRepository = new ObjectRepository($gitPath);
        $useCase = new GitHashObjectUseCase($io, $logger, $gitPath, $objectRepository);

        $result = $useCase($file);

        return match ($result) {
            Result::Failure => self::FAILURE,
            Result::Invalid => self::INVALID,
            Result::Success => self::SUCCESS
        };
    }
}
