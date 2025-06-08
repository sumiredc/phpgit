<?php

declare(strict_types=1);

namespace Phpgit\Request;

use Phpgit\Command\CommandInterface;
use Phpgit\Domain\CommandInput\DiffIndexOptionAction;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

final class DiffIndexRequest extends Request
{
    private function __construct(
        public readonly DiffIndexOptionAction $action,
        public readonly bool $isCached,
        public readonly string $treeIsh,
    ) {}

    public static function setUp(CommandInterface $command): void
    {
        $command
            ->addArgument('tree-ish', InputArgument::REQUIRED, 'The id of a tree object to diff against.')
            ->addOption('cached', null, InputOption::VALUE_NONE, 'Do not consider the on-disk file at all.')
            ->addOption('stat', null, InputOption::VALUE_NONE, 'Generate a diffstat.');

        self::unlock();
    }

    public static function new(InputInterface $input): self
    {
        self::assertNew();

        $treeIsh = strval($input->getArgument('tree-ish'));
        $isCached = boolval($input->getOption('cached'));

        $action = match (true) {
            $input->getOption('stat') => DiffIndexOptionAction::Stat,
            default => DiffIndexOptionAction::Default,
        };

        return new self($action, $isCached, $treeIsh);
    }
}
