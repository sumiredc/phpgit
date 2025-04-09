<?php

namespace Phpgit\Command;

use Phpgit\Domain\GitPath;
use Phpgit\Domain\Result;
use Phpgit\Lib\Logger;
use Phpgit\Repository\ObjectRepository;
use Phpgit\UseCase\GitCatFileUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'git:cat-file',
    description: 'オブジェクトの中身を確認します',
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
        $logger = Logger::console();
        $io = new SymfonyStyle($input, $output);
        $gitPath = new GitPath;
        $objectRepository = new ObjectRepository($gitPath);
        $useCase = new GitCatFileUseCase($io, $logger, $gitPath, $objectRepository);

        $type = boolval($input->getOption('type'));
        $size = boolval($input->getOption('size'));
        $exists = boolval($input->getOption('exists'));
        $prettyPrint = boolval($input->getOption('pretty-print'));
        $object = strval($input->getArgument('object'));

        $result = $useCase($type, $size, $exists, $prettyPrint, $object);

        return match ($result) {
            Result::Failure => self::FAILURE,
            Result::Invalid => self::INVALID,
            Result::Success => self::SUCCESS
        };
    }
}
