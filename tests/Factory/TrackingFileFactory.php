<?php


declare(strict_types=1);

namespace Tests\Factory;

use Phpgit\Domain\TrackingFile;

final class TrackingFileFactory
{
    public static function new(): TrackingFile
    {
        return TrackingFile::new('dummy');
    }
}
