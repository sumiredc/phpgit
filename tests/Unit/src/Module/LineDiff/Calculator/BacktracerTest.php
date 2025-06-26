<?php

declare(strict_types=1);

use Phpgit\Module\LineDiff\Calculator\Backtracer;
use Phpgit\Module\LineDiff\Calculator\Optimizer;
use Phpgit\Module\LineDiff\Domain\DiffResult;
use Phpgit\Module\LineDiff\Domain\Snapshot;
use Phpgit\Module\LineDiff\Domain\Trace;

describe('Backtracer', function () {
    it('builds DiffResult from a simple Trace', function () {
        $optimizer = new Optimizer();
        $backtracer = new Backtracer($optimizer);

        $xLines = ['A', 'G', 'C', 'A', 'T'];
        $yLines = ['G', 'A', 'C'];
        $xMax = count($xLines);
        $yMax = count($yLines);

        $trace = new Trace();

        $snapshot0 = new Snapshot();
        $snapshot0->save(0, 0);
        $trace->save(0, $snapshot0);

        $snapshot1 = new Snapshot();
        $snapshot1->save(-1, 1);
        $snapshot1->save(1, 2);
        $trace->save(1, $snapshot1);

        $snapshot2 = new Snapshot();
        $snapshot2->save(-2, 1);
        $snapshot2->save(0, 3);
        $snapshot2->save(2, 4);
        $trace->save(2, $snapshot2);

        $snapshot3 = new Snapshot();
        $snapshot3->save(-3, 1);
        $snapshot3->save(-1, 3);
        $snapshot3->save(1, 4);
        $snapshot3->save(3, 5);
        $trace->save(3, $snapshot3);

        $actual = $backtracer->build($trace, $xMax, $yMax, $xLines, $yLines);

        expect($actual->toUnifiedString())->toBe("- A\nG\n- C\nA\n- T\n+ C");
    });

    it('returns empty result when xLines and yLines are empty', function () {
        $optimizer = new Optimizer();
        $backtracer = new Backtracer($optimizer);

        $trace = new Trace();
        $snapshot = new Snapshot();
        $snapshot->save(0, 0);
        $trace->save(0, $snapshot);

        $actual = $backtracer->build($trace, 0, 0, [], []);

        expect($actual)->toBeInstanceOf(DiffResult::class);
        expect(count($actual->details))->toBe(0);
    });
});
