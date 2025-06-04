<?php

declare(strict_types=1);

namespace Phpgit\Request;

use LogicException;
use Phpgit\Command\CommandInterface;
use Symfony\Component\Console\Input\InputInterface;

/**
 * The static method `setUp()` must be called before creating a new instance via `new()`.
 * `setUp()` is responsible for unlocking the request by setting `lockStates[class-string]` to false.
 * If `lockStates[class-string]` remains true, calling `new()` should be disallowed to enforce proper initialization.
 */
abstract class Request
{
    /**
     * @var array<class-string<Request>,bool>
     */
    private static array $lockStates = [];

    abstract public static function setUp(CommandInterface $command): void;

    abstract public static function new(InputInterface $input): self;

    protected static function lock(): void
    {
        static::$lockStates[static::class] = true;
    }

    protected static function unlock(): void
    {
        static::$lockStates[static::class] = false;
    }

    /** 
     * @throws LogicException 
     */
    protected static function assertNew(): void
    {
        if (static::$lockStates[static::class] ?? true) {
            throw new LogicException('Cannot instantiate request. Call setUp() first');
        }
    }
}
