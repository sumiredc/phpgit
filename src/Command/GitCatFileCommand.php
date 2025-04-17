<?php

declare(strict_types=1);

namespace Phpgit\Command;

use Phpgit\Domain\CommandInput\GitCatFileOptionType;
use Phpgit\Lib\IO;
use Phpgit\Repository\ObjectRepository;
use Phpgit\UseCase\GitCatFileUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/** @see https://git-scm.com/docs/git-cat-file */
#[AsCommand(
    name: 'git:cat-file',
    description: '',
)]
final class GitCatFileCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('type', 't', InputOption::VALUE_NONE, 'Show object type')
            ->addOption('pretty-print', 'p', InputOption::VALUE_NONE, 'Pretty-print the contents')
            ->addOption('exists', 'e', InputOption::VALUE_NONE, 'Check if object exists')
            ->addOption('size', 's', InputOption::VALUE_NONE, 'Show size of the object')
            ->addArgument('object', InputArgument::REQUIRED, 'Git object name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new IO($input, $output);
        $objectRepository = new ObjectRepository();
        $useCase = new GitCatFileUseCase($io, $objectRepository);

        $type = $this->validateOptionType($input);
        $object = strval($input->getArgument('object'));

        $result = $useCase($type, $object);

        return $result->value;
    }

    private function validateOptionType(InputInterface $input): ?GitCatFileOptionType
    {
        $type = boolval($input->getOption('type'));
        $size = boolval($input->getOption('size'));
        $exists = boolval($input->getOption('exists'));
        $prettyPrint = boolval($input->getOption('pretty-print'));

        return match (true) {
            $type => GitCatFileOptionType::Type,
            $size => GitCatFileOptionType::Size,
            $exists => GitCatFileOptionType::Exists,
            $prettyPrint => GitCatFileOptionType::PrettyPrint,
            default => throw new InvalidOptionException('Not enough options'),
        };
    }
}
