<?php

declare(strict_types=1);

namespace Phpgit\Command;

use Phpgit\Infra\Printer\CliPrinter;
use Phpgit\Infra\Repository\ObjectRepository;
use Phpgit\Request\CatFileRequest;
use Phpgit\UseCase\CatFileUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
        $this
            ->addArgument(
                'object',
                InputArgument::REQUIRED,
                'The name of the object to show.'
            )
            ->addOption(
                'type',
                't',
                InputOption::VALUE_NONE,
                'Instread of the content, show the object type identified by <object>.'
            )
            ->addOption(
                'pretty-print',
                'p',
                InputOption::VALUE_NONE,
                'Pretty-print the contents of <object> based on its type.'
            )
            ->addOption(
                'exists',
                'e',
                InputOption::VALUE_NONE,
                'Exit with zero status if <object> exists and is a valid object.'
            )
            ->addOption(
                'size',
                's',
                InputOption::VALUE_NONE,
                'Instead of the content, show the object size identified by <object>.'
            );
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
