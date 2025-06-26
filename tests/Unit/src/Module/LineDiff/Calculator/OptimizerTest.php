<?php

declare(strict_types=1);

use Phpgit\Module\LineDiff\Calculator\Optimizer;
use Phpgit\Module\LineDiff\Domain\Snapshot;

describe('selectOptimalX', function () {
    it('returns x from insert when k === -d', function () {
        $snapshot = new Snapshot();
        $snapshot->save(0, 5);

        $optimizer = new Optimizer();
        $result = $optimizer->selectOptimalX(1, -1, $snapshot);

        expect($result)->toBe(5);
    });

    it('returns x from delete when k === d', function () {
        $snapshot = new Snapshot();
        $snapshot->save(0, 3);

        $optimizer = new Optimizer();
        $result = $optimizer->selectOptimalX(1, 1, $snapshot);

        expect($result)->toBe(4); // 3 + 1
    });

    it('returns delete when delete >= insert', function () {
        $snapshot = new Snapshot();
        $snapshot->save(1, 4); // insert
        $snapshot->save(-1, 4); // delete

        $optimizer = new Optimizer();
        $result = $optimizer->selectOptimalX(2, 0, $snapshot);

        expect($result)->toBe(5);
    });

    it('returns insert when delete < insert', function () {
        $snapshot = new Snapshot();
        $snapshot->save(1, 7); // insert
        $snapshot->save(-1, 4); // delete

        $optimizer = new Optimizer();
        $result = $optimizer->selectOptimalX(2, 0, $snapshot);

        expect($result)->toBe(7);
    });
});

describe('selectBacktraceOperation', function () {
    it('returns insert when k === -d', function () {
        $snapshot = new Snapshot();

        $optimizer = new Optimizer();
        $result = $optimizer->selectBacktraceOperation(1, -1, $snapshot);

        expect($result)->toBe(0); // k + 1
    });

    it('returns delete when k === d', function () {
        $snapshot = new Snapshot();

        $optimizer = new Optimizer();
        $result = $optimizer->selectBacktraceOperation(1, 1, $snapshot);

        expect($result)->toBe(0); // k - 1
    });

    it('returns delete when delete is prioritized (delete >= insert)', function () {
        $snapshot = new Snapshot();
        $snapshot->save(1, 3); // insert (k+1)
        $snapshot->save(-1, 5); // delete (k-1)

        $optimizer = new Optimizer();
        $result = $optimizer->selectBacktraceOperation(2, 0, $snapshot);

        expect($result)->toBe(-1);
    });

    it('returns insert when insert is smaller than delete', function () {
        $snapshot = new Snapshot();
        $snapshot->save(1, 4); // insert
        $snapshot->save(-1, 3); // delete

        $optimizer = new Optimizer();
        $result = $optimizer->selectBacktraceOperation(2, 0, $snapshot);

        expect($result)->toBe(1);
    });
});
