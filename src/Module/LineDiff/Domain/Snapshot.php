<?php

declare(strict_types=1);

namespace Phpgit\Module\LineDiff\Domain;

use OutOfBoundsException;

final class Snapshot
{
    /**
     * @var array<int,int> [k => x]
     */
    private array $points = [];

    /**
     * @param int $k 対角線
     * @param int $x x 座標
     */
    public function save(int $k, int $x): void
    {
        $this->points[$k] = $x;
    }

    /**
     * @param int $k 対角線
     * @return int $x x 座標
     */
    public function get(int $k): int
    {
        return $this->points[$k]
            ?? throw new OutOfBoundsException(sprintf('Key %d does not exist', $k));
    }
}
