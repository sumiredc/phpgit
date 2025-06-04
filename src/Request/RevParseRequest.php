<?php

declare(strict_types=1);

namespace Phpgit\Request;

use InvalidArgumentException;
use Phpgit\Command\CommandInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

final class RevParseRequest extends Request
{
    /**
     * @param array<string> $refs
     */
    private function __construct(
        public readonly array $args,
    ) {}

    public static function setUp(CommandInterface $command): void
    {
        $command->addArgument('args', InputArgument::IS_ARRAY, 'Separated by space');

        self::unlock();
    }

    public static function new(InputInterface $input): self
    {

        $args = $input->getArgument('args');
        if (!is_array($args)) {
            throw new InvalidArgumentException(
                sprintf('invalid argument because it is not an array: %s', gettype($args))
            );
        }

        return new self($args);
    }
}
