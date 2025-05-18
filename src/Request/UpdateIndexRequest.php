<?php

declare(strict_types=1);

namespace Phpgit\Request;

use InvalidArgumentException;
use Phpgit\Command\CommandInterface;
use Phpgit\Domain\CommandInput\UpdateIndexOptionAction;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

final class UpdateIndexRequest extends Request
{
    private function __construct(
        public readonly UpdateIndexOptionAction $action,
        public readonly string $file,
        public readonly ?string $mode = null,
        public readonly ?string $object = null
    ) {}

    public static function setUp(CommandInterface $command): void
    {
        $command
            ->addOption(
                'add',
                null,
                InputOption::VALUE_NONE,
                'If a specified file isn’t in the index already then it’s added. Default behaviour is to ignore new files.'
            )
            ->addOption(
                'remove',
                null,
                InputOption::VALUE_NONE,
                'If a specified file is in the index but is missing then it’s removed. Default behavior is to ignore removed files.'
            )
            ->addOption(
                'force-remove',
                null,
                InputOption::VALUE_NONE,
                'Remove the file from the index even when the working directory still has such a file.'
            )
            ->addOption(
                'cacheinfo',
                null,
                InputOption::VALUE_NONE,
                'Directly insert the specified info into the index.'
            )
            ->addArgument(
                'arg1',
                InputArgument::OPTIONAL,
                "[--add: required] <file> Files to act on. Note that files beginning with.\n" .
                    "[--cacheinfo: required] <mode> Rewrite to file mode. ex: 10755, 10644"
            )
            ->addArgument(
                'arg2',
                InputArgument::OPTIONAL,
                '[--cacheinfo: required] <object> Rewrite to object hash(sha1).'
            )
            ->addArgument(
                'arg3',
                InputArgument::OPTIONAL,
                '[--cacheinfo: required] <file> Files to act on. Note that files beginning with.'
            );

        self::unlock();
    }

    /** 
     * @throws InvalidOptionException
     * @throws InvalidArgumentException
     */
    public static function new(InputInterface $input): self
    {
        self::assertNew();

        $add = boolval($input->getOption('add'));
        $remove = boolval($input->getOption('remove'));
        $forceRemove = boolval($input->getOption('force-remove'));
        $cacheinfo = boolval($input->getOption('cacheinfo'));

        $action = match (true) {
            $add => UpdateIndexOptionAction::Add,
            $remove => UpdateIndexOptionAction::Remove,
            $forceRemove => UpdateIndexOptionAction::ForceRemove,
            $cacheinfo => UpdateIndexOptionAction::Cacheinfo,
            default => UpdateIndexOptionAction::Add,
        };

        switch ($action) {
            case UpdateIndexOptionAction::Cacheinfo:
                $mode = $input->getArgument('arg1');
                $object = $input->getArgument('arg2');
                $file = $input->getArgument('arg3');

                if (is_null($mode) || is_null($object) || is_null($file)) {
                    throw new InvalidArgumentException('error: option \'cacheinfo\' expects <mode>,<sha1>,<path>');
                }

                return new self($action, strval($file), strval($mode), strval($object));

            default:
                $file = strval($input->getArgument('arg1') ?? '');

                return new self($action, $file);
        }
    }
}
