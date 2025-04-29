<?php

declare(strict_types=1);

namespace Tests\Factory;

use InvalidArgumentException;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\SegmentTree;

final class SegmentTreeFactory
{
    public static function new(): SegmentTree
    {
        return SegmentTree::new();
    }

    /**
     * @param array<string,IndexEntry|array<string,IndexEntry,array>> $values
     */
    public static function fromArray(array $values): SegmentTree
    {
        $factory = new self();

        $tree = SegmentTree::new();
        return $factory->setSegment($tree, $values);
    }

    private function setSegment(SegmentTree &$tree, array $values): SegmentTree
    {
        foreach ($values as $k => $v) {
            if (is_a($v, IndexEntry::class)) {
                $tree->addEntry($k, $v);
                continue;
            }

            if (is_array($v)) {
                $newTree = $tree->addNewSegmentTree($k);
                $this->setSegment($newTree, $values[$k]);
                continue;
            }

            throw new InvalidArgumentException(sprintf('invalid value: %s', $v));
        }

        return $tree;
    }
}
