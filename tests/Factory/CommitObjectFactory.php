<?php

declare(strict_types=1);

namespace Tests\Factory;

use Phpgit\Domain\CommitObject;

final class CommitObjectFactory
{
    public static function new(): CommitObject
    {
        return CommitObject::new(
            ObjectHashFactory::new(),
            GitSignatureFactory::new(),
            GitSignatureFactory::new(),
            'dummy message',
        );
    }
}
