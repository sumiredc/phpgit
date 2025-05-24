<?php

declare(strict_types=1);

namespace Phpgit\Request;

use Phpgit\Command\CommandInterface;
use Phpgit\Domain\CommandInput\AddOptionAction;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

final class AddRequest extends Request
{
    private function __construct(
        public readonly AddOptionAction $action,
        public readonly string $path
    ) {}

    public static function setUp(CommandInterface $command): void
    {
        $command
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                "[--all: unnecessary] don\'t use argument\n"
                    . '[other: required] relative path from project root',
                ''
            )
            ->addOption('all', 'A', InputOption::VALUE_NONE)
            ->addOption('update', 'u', InputOption::VALUE_NONE);

        self::unlock();
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function new(InputInterface $input): self
    {
        self::assertNew();

        $action = match (true) {
            boolval($input->getOption('all')) => AddOptionAction::All,
            boolval($input->getOption('update')) => AddOptionAction::Update,
            default => AddOptionAction::Default,
        };

        $path = $input->getArgument('path');
        if (
            $action === AddOptionAction::Default
            && $path === ''
        ) {
            throw new InvalidArgumentException('Not enough options (missing: "path").');
        }

        return new self($action, $path);
    }
}
