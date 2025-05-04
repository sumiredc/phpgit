<?php


declare(strict_types=1);

namespace Tests\Factory;

use Phpgit\Domain\GitSignature;
use Phpgit\Domain\Timestamp;

final class GitSignatureFactory
{
    public static function new(): GitSignature
    {
        return GitSignature::new(
            'dummy name',
            'dummy@example.com',
            Timestamp::new(),
        );
    }
}
