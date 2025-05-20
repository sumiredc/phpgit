<?php


declare(strict_types=1);

namespace Tests\Factory;

use Phpgit\Domain\TrackedPath;

final class TrackedPathFactory
{
    public static function new(): TrackedPath
    {
        return TrackedPath::parse('dummy');
    }
}
