<?php

declare(strict_types=1);

namespace Phpgit\Module\LineDiff\Calculator;

use Phpgit\Module\LineDiff\Domain\DiffResult;
use Phpgit\Module\LineDiff\Domain\Operation;
use Phpgit\Module\LineDiff\Domain\Trace;

/**
 * forward search で記録した trace をたどり
 * 最短経路を算出・差分の結果を返却する
 */
readonly final class Backtracer
{
    public function __construct(
        private readonly Optimizer $optimizer
    ) {}

    /**
     * @param Trace $trace
     * @param int $x ゴール地点 x 座標
     * @param int $y ゴール地点 y 座標
     * @param array<string> $xLines
     * @param array<string> $yLines
     * @return DiffResult
     */
    public function build(
        Trace $trace,
        int $x,
        int $y,
        array $xLines,
        array $yLines,
    ): DiffResult {
        $result = new DiffResult;
        $d = count($trace) - 1; // 最短の編集距離

        // 編集距離を逆順で走査
        while ($d >= 0) {
            $snapshot = $trace->get($d);
            $k = $x - $y;

            // snake 移動の判定
            while (
                $x > 0
                && $y > 0
                && $xLines[$x - 1] === $yLines[$y - 1]
            ) {
                // 1つ前の x, y の文字が一致する場合は snake 移動
                $result->add(Operation::Equal, $x, $xLines[$x - 1]);
                $x--;
                $y--;
            }

            // 始点にたどり着いた場合は走査終了
            if ($x === 0 && $y === 0) {
                break;
            }

            // どのアクションで現在位置にたどり着いたか判定して、前の k を算出
            // d は開始位置の編集距離がループされているので、+1 して渡す
            $prevK = $this->optimizer->selectBacktraceOperation($d + 1, $k, $snapshot);

            // 1つ前の x 座標を算出
            $prevX = $snapshot->get($prevK);

            // 1つ前の x 座標から、Insert or Delete を判定
            if ($x === $prevX) {
                // Insert 
                // x 座標が変わっていないので縦移動
                // 縦移動のため、y 座標を decrement
                $result->add(Operation::Insert, $y, $yLines[--$y]);
            } else {
                // Delete
                // x 座標が変わっているので横移動
                // 横移動のため、x 座標を decrement
                $result->add(Operation::Delete, $x, $xLines[--$x]);
            };

            $d--;
        }

        return $result->reverse();
    }
}
