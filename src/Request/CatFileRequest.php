<?php

declare(strict_types=1);

namespace Phpgit\Request;

use Phpgit\Domain\CommandInput\CatFileOptionType;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;

readonly final class CatFileRequest
{
    private function __construct(
        public readonly CatFileOptionType $type,
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
            $type => CatFileOptionType::Type,
            $size => CatFileOptionType::Size,
            $exists => CatFileOptionType::Exists,
            $prettyPrint => CatFileOptionType::PrettyPrint,
            default => throw new InvalidOptionException('Not enough options'),
        };

        $object = strval($input->getArgument('object'));

        return new self($optionType, $object);
    }
}
