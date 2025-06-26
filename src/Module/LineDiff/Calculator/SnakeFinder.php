<?php

declare(strict_types=1);

namespace Phpgit\Module\LineDiff\Calculator;

readonly final class SnakeFinder
{
    /**
     * @param array<string> $xLines
     * @param array<string> $yLines
     */
    public function calculateByForward(
        int $x,
        int $y,
        int $xMax,
        int $yMax,
        array $xLines,
        array $yLines
    ): int {
        $times = 0;

        while (
            $x < $xMax
            && $y < $yMax
            && $xLines[$x] === $yLines[$y]
        ) {
            $times++;
            $x++;
            $y++;
        }

        return $times;
    }
}
