<?php

declare(strict_types=1);

namespace Tests\Factory;

use Phpgit\Domain\Reference;
use Phpgit\Domain\ReferenceType;

final class ReferenceFactory
{
    public static function new(ReferenceType $refType): Reference
    {
        return Reference::new($refType, 'dummy');
    }

    public static function local(): Reference
    {
        return self::new(ReferenceType::Local);
    }

    public static function remote(): Reference
    {
        return self::new(ReferenceType::Remote);
    }

    public static function tag(): Reference
    {
        return self::new(ReferenceType::Tag);
    }
}
