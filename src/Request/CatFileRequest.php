<?php

declare(strict_types=1);

namespace Phpgit\Request;

use Phpgit\Command\CommandInterface;
use Phpgit\Domain\CommandInput\CatFileOptionType;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

final class CatFileRequest extends Request
{
    private function __construct(
        public readonly CatFileOptionType $type,
        public readonly string $object,
    ) {}

    public static function setUp(CommandInterface $command): void
    {
        $command
            ->addArgument(
                'object',
                InputArgument::REQUIRED,
                'The name of the object to show.'
            )
            ->addOption(
                'type',
                't',
                InputOption::VALUE_NONE,
                'Instread of the content, show the object type identified by <object>.'
            )
            ->addOption(
                'pretty-print',
                'p',
                InputOption::VALUE_NONE,
                'Pretty-print the contents of <object> based on its type.'
            )
            ->addOption(
                'exists',
                'e',
                InputOption::VALUE_NONE,
                'Exit with zero status if <object> exists and is a valid object.'
            )
            ->addOption(
                'size',
                's',
                InputOption::VALUE_NONE,
                'Instead of the content, show the object size identified by <object>.'
            );

        self::unlock();
    }

    /** 
     * @throws InvalidOptionException 
     */
    public static function new(InputInterface $input): self
    {
        self::assertNew();

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
