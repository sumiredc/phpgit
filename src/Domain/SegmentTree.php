<?php

declare(strict_types=1);

namespace Phpgit\Domain;

use BadMethodCallException;
use InvalidArgumentException;

final class SegmentTree
{
    /** @var array<string,IndexEntry|SegmentTree> key: segment name */
    public private(set) array $segments = [];

    private function __construct() {}

    public static function new(): self
    {
        return new self();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function addEntry(string $segmentName, IndexEntry $entry): void
    {
        if (array_key_exists($segmentName, $this->segments)) {
            throw new InvalidArgumentException(sprintf('already exists key: %s', $segmentName));
        }

        $this->segments[$segmentName] = $entry;
    }

    /**
     * @return self added SegmentTree object
     * @throws InvalidArgumentException
     */
    public function addNewSegmentTree(string $segmentName): self
    {
        if (array_key_exists($segmentName, $this->segments)) {
            throw new InvalidArgumentException(sprintf('already exists key: %s', $segmentName));
        }

        $this->segments[$segmentName] = self::new();

        return $this->segments[$segmentName];
    }

    public function isExists(string $segmentName): bool
    {
        return array_key_exists($segmentName, $this->segments);
    }

    /**
     * @throws BadMethodCallException
     */
    public function getEntry(string $segmentName): IndexEntry
    {
        $entry = $this->segments[$segmentName] ?? 'not exists';

        if (is_a($entry, IndexEntry::class)) {
            return $entry;
        }

        throw new BadMethodCallException(sprintf('is not entry: %s', $segmentName));
    }

    /**
     * @throws BadMethodCallException

     */
    public function getSegmentTree(string $segmentName): self
    {
        $tree = $this->segments[$segmentName] ?? 'not exists';

        if (is_a($tree, SegmentTree::class)) {
            return $tree;
        }

        throw new BadMethodCallException(sprintf('is not segment tree: %s', $segmentName));
    }
}
