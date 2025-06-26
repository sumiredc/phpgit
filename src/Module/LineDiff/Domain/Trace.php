<?php

declare(strict_types=1);

namespace Phpgit\Module\LineDiff\Domain;

use Countable;
use OutOfRangeException;

final class Trace implements Countable
{
    /**
     * @var array<int,Snapshot> [d => Snapshot] 
     */
    private array $snapshots = [];

    /**
     * @param int $d 編集距離
     */
    public function save(int $d, Snapshot $s): void
    {
        $this->snapshots[$d] = $s;
    }

    /**
     * @param int $d 編集距離
     */
    public function get(int $d): Snapshot
    {
        return $this->snapshots[$d]
            ?? throw new OutOfRangeException(sprintf('Key %d does not exist', $d));
    }

    public function count(): int
    {
        return count($this->snapshots);
    }
}
