<?php

declare(strict_types=1);

namespace Tests\Factory;

use Phpgit\Domain\BlobObject;

final class BlobObjectFactory
{
    public static function new(): BlobObject
    {
        return BlobObject::new('dummy contents');
    }
}
