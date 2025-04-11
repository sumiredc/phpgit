<?php

namespace Phpgit\Command;

use Phpgit\Domain\CommandInput\GitUpdateIndexOptionAction;
use Phpgit\Domain\Result;
use Phpgit\Lib\Logger;
use Phpgit\Repository\FileRepository;
use Phpgit\Repository\IndexRepository;
use Phpgit\Repository\ObjectRepository;
use Phpgit\UseCase\GitUpdateIndexUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'git:update-index',
    description: 'インデックスの更新をします',
)]
final class GitUpdateIndexCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('add', null, InputOption::VALUE_NONE, 'Add file in the index')
            ->addOption('remove', null, InputOption::VALUE_NONE, 'Remove file in the index')
            ->addOption('force-remove', null, InputOption::VALUE_NONE, 'Force remove file in the index')
            ->addOption('replace', null, InputOption::VALUE_NONE, 'Replace file in the index')
            ->addArgument('file', InputArgument::REQUIRED, 'File path');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = Logger::console();
        $io = new SymfonyStyle($input, $output);
        $objectRepository = new ObjectRepository;
        $fileRepository = new FileRepository;
        $indexRepository = new IndexRepository;
        $useCase = new GitUpdateIndexUseCase(
            $io,
            $logger,
            $objectRepository,
            $fileRepository,
            $indexRepository
        );

        $action = $this->validateArgumentAction($input);

        if (is_null($action)) {
            $io->warning("missing required action argument");

            return self::INVALID;
        }

        $file = $input->getArgument('file');
        $result = $useCase($action, $file);

        return match ($result) {
            Result::Failure => self::FAILURE,
            Result::Invalid => self::INVALID,
            Result::Success => self::SUCCESS
        };
    }

    private function validateArgumentAction(InputInterface $input): ?GitUpdateIndexOptionAction
    {
        $add = boolval($input->getOption('add'));
        $remove = boolval($input->getOption('remove'));
        $forceRemove = boolval($input->getOption('force-remove'));
        $replace = boolval($input->getOption('replace'));

        return match (true) {
            $add => GitUpdateIndexOptionAction::Add,
            $remove => GitUpdateIndexOptionAction::Remove,
            $forceRemove => GitUpdateIndexOptionAction::ForceRemove,
            $replace => GitUpdateIndexOptionAction::Replace,
            default => null,
        };
    }
}
