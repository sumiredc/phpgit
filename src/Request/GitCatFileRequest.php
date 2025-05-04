<?php

declare(strict_types=1);

namespace Phpgit\Request;

use Phpgit\Domain\CommandInput\GitCatFileOptionType;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;

readonly final class GitCatFileRequest
{
    private function __construct(
        public readonly GitCatFileOptionType $type,
        public readonly string $object,
    ) {}

    /** @throws InvalidOptionException */
    public static function new(InputInterface $input): self
    {
        $type = boolval($input->getOption('type'));
        $size = boolval($input->getOption('size'));
        $exists = boolval($input->getOption('exists'));
        $prettyPrint = boolval($input->getOption('pretty-print'));

        $optionType = match (true) {
            $type => GitCatFileOptionType::Type,
            $size => GitCatFileOptionType::Size,
            $exists => GitCatFileOptionType::Exists,
            $prettyPrint => GitCatFileOptionType::PrettyPrint,
            default => throw new InvalidOptionException('Not enough options'),
        };

        $object = strval($input->getArgument('object'));

        return new self($optionType, $object);
    }
}
