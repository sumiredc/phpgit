<?php


declare(strict_types=1);

namespace Tests\Factory;

use Phpgit\Domain\TrackingPath;

final class TrackingPathFactory
{
    public static function new(): TrackingPath
    {
        return TrackingPath::new('dummy');
    }
}
