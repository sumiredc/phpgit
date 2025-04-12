<?php

declare(strict_types=1);

namespace Phpgit\Command;

use Phpgit\Domain\Result;
use Phpgit\Lib\IO;
use Phpgit\Lib\Logger;
use Phpgit\UseCase\GitInitUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/** @see https://git-scm.com/docs/git-init */
#[AsCommand(
    name: 'git:init',
    description: '',
)]
final class GitInitCommand extends Command
{
    protected function configure(): void {}

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new IO($input, $output);
        $useCase = new GitInitUseCase($io);

        $result = $useCase();

        return match ($result) {
            Result::Failure => self::FAILURE,
            Result::Invalid => self::INVALID,
            Result::Success => self::SUCCESS
        };
    }
}
