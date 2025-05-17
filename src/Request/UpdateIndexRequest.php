<?php

declare(strict_types=1);

namespace Phpgit\Request;

use InvalidArgumentException;
use Phpgit\Domain\CommandInput\UpdateIndexOptionAction;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;

readonly final class UpdateIndexRequest
{
    private function __construct(
        public readonly UpdateIndexOptionAction $action,
        public readonly string $file,
        public readonly ?string $mode = null,
        public readonly ?string $object = null
    ) {}

    /** 
     * @throws InvalidOptionException
     * @throws InvalidArgumentException
     */
    public static function new(InputInterface $input): self
    {
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
                $mode = $input->getArgument('mode');
                $object = $input->getArgument('object');
                $file = $input->getArgument('file');

                if (is_null($mode) || is_null($object) || is_null($file)) {
                    throw new InvalidArgumentException('error: option \'cacheinfo\' expects <mode>,<sha1>,<path>');
                }

                return new self($action, strval($file), strval($mode), strval($object));

            default:
                // NOTE: first argument is "file"
                $file = strval($input->getArgument('mode') ?? '');

                return new self($action, $file);
        }
    }
}
