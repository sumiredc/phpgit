<?php

declare(strict_types=1);

namespace Phpgit\Request;

use Phpgit\Command\CommandInterface;
use Phpgit\Domain\CommandInput\LsFilesOptionAction;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

final class LsFilesRequest extends Request
{
    private function __construct(
        public readonly LsFilesOptionAction $action,
    ) {}

    public static function setUp(CommandInterface $command): void
    {
        $command
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

        self::unlock();
    }

    public static function new(InputInterface $input): self
    {
        self::assertNew();

        $tag = boolval($input->getOption('tag'));
        $zero = boolval($input->getOption('zero'));
        $stage = boolval($input->getOption('stage'));
        $debug = boolval($input->getOption('debug'));

        $action = match (true) {
            $tag => LsFilesOptionAction::Tag,
            $zero => LsFilesOptionAction::Zero,
            $stage => LsFilesOptionAction::Stage,
            $debug => LsFilesOptionAction::Debug,
            default => LsFilesOptionAction::Default,
        };

        return new self($action);
    }
}
