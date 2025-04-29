<?php


declare(strict_types=1);

namespace Tests\Factory;

use Phpgit\Domain\IndexEntry;

final class IndexEntryFactory
{
    public static function new(): IndexEntry
    {
        return IndexEntry::new(
            FileStatFactory::new(),
            ObjectHashFactory::new(),
            TrackingFileFactory::new(),
        );
    }
}
