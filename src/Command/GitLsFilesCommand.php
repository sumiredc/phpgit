<?php

declare(strict_types=1);

namespace Phpgit\Command;

use Phpgit\Domain\CommandInput\GitLsFileOptionAction;
use Phpgit\Domain\Result;
use Phpgit\Lib\IO;
use Phpgit\Repository\IndexRepository;
use Phpgit\UseCase\GitLsFilesUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/** @see https://git-scm.com/docs/git-ls-files */
#[AsCommand(
    name: 'git:ls-files',
    description: '',
)]
final class GitLsFilesCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption(
                'tag',
                't',
                InputOption::VALUE_NONE,
                'Show status tags together with filenames.'
            )
            ->addOption(
                'zero',
                'z',
                InputOption::VALUE_NONE,
                '\0 line termination on output and do not quote filenames.'
            )
            ->addOption(
                'stage',
                's',
                InputOption::VALUE_NONE,
                'Show staged contents\' mode bits, object name and stage number in the output.'
            )
            ->addOption(
                'debug',
                null,
                InputOption::VALUE_NONE,
                'After each line that describes a file, add more data about its cache entry. This is intended to show as much information as possible for manual inspection; the exact format may change at any time.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new IO($input, $output);
        $indexRepository = new IndexRepository();
        $useCase = new GitLsFilesUseCase($io, $indexRepository);

        $action = $this->validateOptionAction($input);

        $result = $useCase($action);

        return match ($result) {
            Result::Failure => self::FAILURE,
            Result::Invalid => self::INVALID,
            Result::Success => self::SUCCESS
        };
    }

    private function validateOptionAction(InputInterface $input): GitLsFileOptionAction
    {
        return match (true) {
            boolval($input->getOption('tag')) => GitLsFileOptionAction::Tag,
            boolval($input->getOption('zero')) => GitLsFileOptionAction::Zero,
            boolval($input->getOption('stage')) => GitLsFileOptionAction::Stage,
            boolval($input->getOption('debug')) => GitLsFileOptionAction::Debug,
            default =>  GitLsFileOptionAction::Default,
        };
    }
}
