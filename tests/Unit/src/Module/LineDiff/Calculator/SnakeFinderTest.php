<?php

declare(strict_types=1);

use Phpgit\Module\LineDiff\Calculator\SnakeFinder;

describe('SnakeFinder::calculateByForward', function () {
    it('returns 0 when lines differ immediately', function () {
        $finder = new SnakeFinder();

        $xLines = ['a', 'b', 'c'];
        $yLines = ['x', 'y', 'z'];

        $result = $finder->calculateByForward(0, 0, 3, 3, $xLines, $yLines);

        expect($result)->toBe(0);
    });

    it('returns correct count of matching lines from start position', function () {
        $finder = new SnakeFinder();

        $xLines = ['a', 'b', 'c', 'd'];
        $yLines = ['a', 'b', 'x', 'y'];

        $result = $finder->calculateByForward(0, 0, 4, 4, $xLines, $yLines);

        expect($result)->toBe(2);
    });

    it('stops at xMax or yMax boundaries', function () {
        $finder = new SnakeFinder();

        $xLines = ['a', 'b'];
        $yLines = ['a', 'b'];

        $result = $finder->calculateByForward(0, 0, 1, 1, $xLines, $yLines);

        expect($result)->toBe(1);
    });

    it('returns 0 when start positions are at or beyond max boundaries', function () {
        $finder = new SnakeFinder();

        $xLines = ['a'];
        $yLines = ['a'];

        $result1 = $finder->calculateByForward(1, 0, 1, 1, $xLines, $yLines);
        $result2 = $finder->calculateByForward(0, 1, 1, 1, $xLines, $yLines);
        $result3 = $finder->calculateByForward(1, 1, 1, 1, $xLines, $yLines);

        expect($result1)->toBe(0);
        expect($result2)->toBe(0);
        expect($result3)->toBe(0);
    });
});
