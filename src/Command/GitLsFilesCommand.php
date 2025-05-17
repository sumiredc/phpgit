<?php

declare(strict_types=1);

namespace Phpgit\Command;

use Phpgit\Infra\Printer\CliPrinter;
use Phpgit\Infra\Repository\IndexRepository;
use Phpgit\Request\GitLsFilesRequest;
use Phpgit\UseCase\GitLsFilesUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/** @see https://git-scm.com/docs/git-ls-files */
#[AsCommand(
    name: 'git:ls-files',
    description: 'Show information about files in the index and the working tree',
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
        $request = GitLsFilesRequest::new($input);

        $printer = new CliPrinter($input, $output);
        $indexRepository = new IndexRepository();
        $useCase = new GitLsFilesUseCase($printer, $indexRepository);

        $result = $useCase($request);

        return $result->value;
    }
}
