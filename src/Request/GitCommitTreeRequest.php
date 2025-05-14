<?php

declare(strict_types=1);

namespace Phpgit\Request;

use Phpgit\Command\CommandInterface;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

final class GitCommitTreeRequest extends Request
{
    private function __construct(
        public readonly string $tree,
        public readonly string $message,
        public readonly string $parent,
    ) {}

    public static function setUp(CommandInterface $command): void
    {
        $command
            ->addArgument(
                'tree',
                InputArgument::REQUIRED,
                'An existing tree object.'
            )
            ->addOption(
                'message',
                'm',
                InputOption::VALUE_REQUIRED,
                'A paragraph in the commit log message. This can be given more than once and each <message> becomes its own paragraph.'
            )
            ->addOption(
                'parent',
                'p',
                InputOption::VALUE_REQUIRED,
                'Each -p indicates the id of a parent commit object.'
            );

        static::unlock();
    }

    /**
     * @throws InvalidArgumentException
     * @throws InvalidOptionException
     */
    public static function new(InputInterface $input): self
    {
        static::assertNew();

        $tree = strval($input->getArgument('tree'));

        $message = strval($input->getOption('message'));
        if ($message === '') {
            throw new InvalidOptionException('Not enough options (missing: "message").');
        }

        $parent = strval($input->getOption('parent'));

        return new self($tree, $message, $parent);
    }
}
