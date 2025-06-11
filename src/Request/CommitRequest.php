<?php

declare(strict_types=1);

namespace Phpgit\Request;

use Phpgit\Command\CommandInterface;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

final class CommitRequest extends Request
{
    private function __construct(
        public readonly string $message,
    ) {}

    public static function setUp(CommandInterface $command): void
    {
        $command
            ->addOption(
                'message',
                'm',
                InputOption::VALUE_REQUIRED,
                'A paragraph in the commit log message. This can be given more than once and each <message> becomes its own paragraph.'
            );

        self::unlock();
    }

    /**
     * @throws InvalidArgumentException
     * @throws InvalidOptionException
     */
    public static function new(InputInterface $input): self
    {
        self::assertNew();

        $message = strval($input->getOption('message'));
        if ($message === '') {
            throw new InvalidOptionException('Not enough options (missing: "message").');
        }

        return new self($message);
    }
}
