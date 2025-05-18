<?php

declare(strict_types=1);

namespace Phpgit\Request;

use Phpgit\Command\CommandInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

final class HashObjectRequest extends Request
{
    private function __construct(
        public readonly string $file,
    ) {}

    public static function setUp(CommandInterface $command): void
    {
        $command->addArgument('file', InputArgument::REQUIRED, 'The file to hash');

        self::unlock();
    }

    public static function new(InputInterface $input): self
    {
        self::assertNew();

        $file = strval($input->getArgument('file'));

        return new self($file);
    }
}
