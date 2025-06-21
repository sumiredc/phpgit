<?php

declare(strict_types=1);

namespace Tests\Factory;

use Phpgit\Domain\GitFileMode;
use Phpgit\Domain\ObjectType;
use Phpgit\Domain\TreeEntry;

final class TreeEntryFactory
{
    public static function new(): TreeEntry
    {
        return TreeEntry::new(
            ObjectType::Blob,
            GitFileMode::DefaultFile,
            'dummy-file',
            ObjectHashFactory::new()
        );
    }

    public static function blob(): TreeEntry
    {
        return TreeEntry::new(
            ObjectType::Blob,
            GitFileMode::DefaultFile,
            'dummy-blob',
            ObjectHashFactory::new()
        );
    }

    public static function tree(): TreeEntry
    {
        return TreeEntry::new(
            ObjectType::Tree,
            GitFileMode::Tree,
            'dummy-tree',
            ObjectHashFactory::new()
        );
    }
}
