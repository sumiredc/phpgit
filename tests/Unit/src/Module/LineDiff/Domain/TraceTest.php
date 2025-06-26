<?php

declare(strict_types=1);

use Phpgit\Module\LineDiff\Domain\Trace;
use Phpgit\Module\LineDiff\Domain\Snapshot;

describe('Trace', function () {
    it('saves and retrieves Snapshots by distance (d)', function () {
        $trace = new Trace();

        $s1 = new Snapshot();
        $s2 = new Snapshot();

        $trace->save(0, $s1);
        $trace->save(1, $s2);

        expect($trace->get(0))->toBe($s1);
        expect($trace->get(1))->toBe($s2);
    });

    it('overwrites existing Snapshot with same distance (d)', function () {
        $trace = new Trace();

        $original = new Snapshot();
        $replacement = new Snapshot();

        $trace->save(2, $original);
        expect($trace->get(2))->toBe($original);

        $trace->save(2, $replacement);
        expect($trace->get(2))->toBe($replacement);
    });

    it('throws OutOfRangeException when getting unknown distance (d)', function () {
        $trace = new Trace();

        expect(fn() => $trace->get(42))
            ->toThrow(OutOfRangeException::class, 'Key 42 does not exist');
    });

    it('returns the correct count of saved Snapshots', function () {
        $trace = new Trace();

        expect(count($trace))->toBe(0);

        $trace->save(0, new Snapshot());
        $trace->save(1, new Snapshot());

        expect(count($trace))->toBe(2);
    });
});
