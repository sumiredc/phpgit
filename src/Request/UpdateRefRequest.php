<?php

declare(strict_types=1);

namespace Phpgit\Request;

use LogicException;
use Phpgit\Command\CommandInterface;
use Phpgit\Domain\CommandInput\UpdateRefOptionAction;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

final class UpdateRefRequest extends Request
{
    private function __construct(
        public readonly UpdateRefOptionAction $action,
        public readonly string $ref,
        public readonly ?string $newValue,
        public readonly ?string $oldValue,
    ) {}

    public static function setUp(CommandInterface $command): void
    {
        $command
            ->addOption('delete', 'd', InputOption::VALUE_NONE)
            ->addArgument('ref', InputArgument::REQUIRED)
            ->addArgument('arg1', InputArgument::OPTIONAL, 'update: <newvalue:REQUIRED>, delete: <oldvalue:OPTIONAL>')
            ->addArgument('arg2', InputArgument::OPTIONAL, 'update: <oldvalue:OPTIONAL>');

        static::unlock();
    }

    /** 
     * @throws InvalidArgumentException 
     */
    public static function new(InputInterface $input): self
    {
        static::assertNew();

        $action = match (true) {
            boolval($input->getOption('delete')) => UpdateRefOptionAction::Delete,
            default => UpdateRefOptionAction::Update,
        };
        $ref = strval($input->getArgument('ref'));

        switch ($action) {
            case UpdateRefOptionAction::Update:
                $newValue = strval($input->getArgument('arg1'));
                $oldValue = strval($input->getArgument('arg2'));

                return new self($action, $ref, $newValue, $oldValue);

            case UpdateRefOptionAction::Delete:
                $oldValue = strval($input->getArgument('arg1'));

                return new self($action, $ref, null, $oldValue);

            default:
                throw new LogicException('Unexpected default case reached in switch'); // @codeCoverageIgnore
        }
    }
}
