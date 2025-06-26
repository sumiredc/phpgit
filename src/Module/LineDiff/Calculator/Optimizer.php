<?php

declare(strict_types=1);

namespace Phpgit\Module\LineDiff\Calculator;

use Phpgit\Module\LineDiff\Domain\Snapshot;

readonly final class Optimizer
{
    /**
     * 編集距離 d でたどり着ける対角線 k の最適な x 座標を決定
     * 
     * @param int $d
     * @param int $k
     * @param Snapshot $snapshot
     * @return int x
     * 
     * Insert = 下移動
     * Delete = 右移動
     * 
     * $snapshot->get($k - 1) = Prev k の x 座標
     * $snapshot->get($k + 1) = Next k の x 座標, Insert で移動してきた場合の x 座標 (下移動のため next と同じ)
     * $snapshot->get($k - 1) + 1 = Delete で現在位置へきた場合の x 座標 (右移動のため prev + 1)
     */
    public function selectOptimalX(int $d, int $k, Snapshot $snapshot): int
    {
        // Insert
        // 一番下の対角線のため、Next k 側からの移動でしかたどり着けない
        if ($k === -$d) {
            return $snapshot->get($k + 1);
        }

        // Delete
        // 一番上の対角線のため、Prev k 側からの移動でしかたどり着けない
        if ($k === $d) {
            return $snapshot->get($k - 1) + 1;
        }

        // NOTE: 上記 2 条件が満たされているため、Undefined array key にならない
        $delete = $snapshot->get($k - 1) + 1;
        $insert = $snapshot->get($k + 1);

        // 削除優先 (Git と同等)
        if ($delete >= $insert) {
            return $delete;
        }

        return $insert;
    }

    /**
     * d, d+1 間のアクションを判定する
     * 
     * Insert = 下移動でたどり着いた
     * Delete = 右移動でたどり着いた
     * 
     * @param int $d 編集距離
     * @param int $k 対角線
     * @param Snapshot $v
     * @return int prevK
     */
    public function selectBacktraceOperation(
        int $d,
        int $k,
        Snapshot $snapshot
    ): int {
        $insert = $k + 1;
        $delete = $k - 1;

        if ($k === -$d) {
            // Insert
            // 下側の k なので、下移動でしかたどり着けない
            return $insert;
        }

        if ($k === $d) {
            // Delete
            // 上側の k なので、右移動でしかたどり着けない
            return $delete;
        }

        $prevXByInsert = $snapshot->get($k + 1); // 上側の k に打たれた x (Insert)
        $prevXByDelete = $snapshot->get($k - 1); // 下側の x に打たれた x (Delete)

        // 削除優先 (Git と同等)
        if ($prevXByInsert <= $prevXByDelete) {
            return $delete;
        }

        return $insert;
    }
}
