<?php

declare(strict_types=1);

namespace Phpgit\Module\LineDiff;

use Phpgit\Module\LineDiff\Calculator\Backtracer;
use Phpgit\Module\LineDiff\Calculator\ForwardSearcher;
use Phpgit\Module\LineDiff\Calculator\Optimizer;
use Phpgit\Module\LineDiff\Calculator\SnakeFinder;
use Phpgit\Module\LineDiff\Domain\DiffResult;

/**
 * Myers Argorithm
 * @see http://www.xmailserver.org/diff2.pdf
 * 
 * x,y: 座標
 * k: 対角線
 * d: 編集距離
 * 
 * snake: x,y それぞれの文字が一致した場合に斜め移動すること
 * 
 * 対角線の算出
 *  k = x - y
 * 
 * y 座標の逆算
 *  y = x - k
 * 
 * 偶奇制約
 *  d = x + y
 *  d,k の関係は偶,偶 or 奇,奇 にしかならない
 */
readonly final class Differ
{
    public function __invoke(string $old, string $new): DiffResult
    {
        $snakeFinder = new SnakeFinder;
        $optimizer = new Optimizer;

        $forward = new ForwardSearcher($snakeFinder, $optimizer);
        $backtracer = new Backtracer($optimizer);

        $xLines = explode("\n", $old);
        $yLines = explode("\n", $new);

        $xMax = count($xLines);
        $yMax = count($yLines);

        $trace = $forward->search($xMax, $yMax, $xLines, $yLines);

        return $backtracer->build($trace, $xMax, $yMax, $xLines, $yLines);
    }
}
