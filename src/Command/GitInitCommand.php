<?php

namespace Phpgit\Command;

use Phpgit\Domain\GitPath;
use Phpgit\Domain\Result;
use Phpgit\Lib\Logger;
use Phpgit\UseCase\GitInitUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'git:init',
    description: '空の git リポジトリを作成します',
)]
final class GitInitCommand extends Command
{
    protected function configure(): void {}

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = Logger::console();
        $io = new SymfonyStyle($input, $output);
        $useCase = new GitInitUseCase($io, $logger, new GitPath);

        $result = $useCase();

        return match ($result) {
            Result::Failure => self::FAILURE,
            Result::Invalid => self::INVALID,
            Result::Success => self::SUCCESS
        };
    }
}
