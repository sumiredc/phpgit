<?php

declare(strict_types=1);

namespace Tests\Factory;

use Phpgit\Domain\TreeObject;

final class TreeObjectFactory
{
    public static function new(): TreeObject
    {
        return TreeObject::new();
    }
}
