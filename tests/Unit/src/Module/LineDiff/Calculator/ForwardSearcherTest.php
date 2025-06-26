<?php

declare(strict_types=1);

use Phpgit\Module\LineDiff\Calculator\ForwardSearcher;
use Phpgit\Module\LineDiff\Calculator\Optimizer;
use Phpgit\Module\LineDiff\Calculator\SnakeFinder;
use Phpgit\Module\LineDiff\Domain\Trace;

describe('ForwardSearcher', function () {
    it('returns a trace that reaches the goal', function () {
        $snakeFinder = new SnakeFinder();
        $optimizer = new Optimizer();
        $forwardSearcher = new ForwardSearcher($snakeFinder, $optimizer);

        $xLines = ['A', 'B', 'C'];
        $yLines = ['A', 'X', 'C'];

        $xMax = count($xLines);
        $yMax = count($yLines);

        $trace = $forwardSearcher->search($xMax, $yMax, $xLines, $yLines);

        expect($trace)->toBeInstanceOf(Trace::class);
        expect(count($trace))->toBeGreaterThan(0);
    });

    it('returns immediately when xLines and yLines are empty', function () {
        $snakeFinder = new SnakeFinder();
        $optimizer = new Optimizer();
        $forwardSearcher = new ForwardSearcher($snakeFinder, $optimizer);

        $xLines = [];
        $yLines = [];

        $xMax = 0;
        $yMax = 0;

        $trace = $forwardSearcher->search($xMax, $yMax, $xLines, $yLines);

        expect($trace)->toBeInstanceOf(Trace::class);
        expect(count($trace))->toBe(0);
    });
});
