<?php

declare(strict_types=1);

namespace Phpgit\Module\LineDiff\Calculator;

use LogicException;
use Phpgit\Module\LineDiff\Domain\Snapshot;
use Phpgit\Module\LineDiff\Domain\Trace;

readonly final class ForwardSearcher
{
    public function __construct(
        private readonly SnakeFinder $snakeFinder,
        private readonly Optimizer $optimizer
    ) {}

    /**
     * @param array<int,string> $xLines
     * @param array<int,string> $yLines
     */
    public function search(
        int $xMax,
        int $yMax,
        array $xLines,
        array $yLines
    ): Trace {
        $max = $xMax + $yMax;
        $x = 0;

        // 編集距離ごとの各対角線上にある x の履歴
        $trace = new Trace;

        // 編集距離を 0 から順にチェック
        for ($d = 0; $d <= $max; $d++) {
            // 1つ前の編集距離での x 座標情報を準備
            // 初回は存在しないため、空配列を準備
            $prevSnapshot = $d === 0 ? new Snapshot : $trace->get($d - 1);

            // 走査する編集距離用の x 座標格納用配列を準備
            $snapshot = new Snapshot;

            // 0 を中心に対角線 k の +- の範囲をチェック
            // x + y = d の偶奇制約があるため、対角線(k) は +2
            for ($k = -$d; $k <= $d; $k += 2) {
                // 編集距離が 0 でなければ、最短の x を算出する
                if ($d !== 0) {
                    $x = $this->optimizer->selectOptimalX($d, $k, $prevSnapshot);
                }

                // y 座標の算出
                $y = $x - $k;

                // 現在位置から可能な限りの対角線移動をする
                $snake = $this->snakeFinder->calculateByForward($x, $y, $xMax, $yMax, $xLines, $yLines);
                $x += $snake;
                $y += $snake;

                // k の最大 x を記録
                $snapshot->save($k, $x);

                if ($this->isAtGoal($x, $y, $xMax, $yMax)) {
                    return $trace;
                }
            }

            // ループ終了時に結果を記録
            $trace->save($d, $snapshot);
        }

        throw new LogicException('search failed: could not find a path to goal within max edit distance'); // @codeCoverageIgnore
    }

    private function isAtGoal(
        int $x,
        int $y,
        int $xMax,
        int $yMax,
    ): bool {
        return $x >= $xMax && $y >= $yMax;
    }
}
