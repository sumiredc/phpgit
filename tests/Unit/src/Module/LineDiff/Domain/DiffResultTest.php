<?php

declare(strict_types=1);

use Phpgit\Module\LineDiff\Domain\DiffResult;
use Phpgit\Module\LineDiff\Domain\Operation;

describe('reverse', function () {
    it('reverses the order of diff operations', function () {
        $diffResult = new DiffResult;

        $diffResult->add(Operation::Delete, 3, 'line3');
        $diffResult->add(Operation::Insert, 2, 'line2');
        $diffResult->add(Operation::Equal, 1, 'line1');

        $expected = [
            [Operation::Equal, 1, 'line1'],
            [Operation::Insert, 2, 'line2'],
            [Operation::Delete, 3, 'line3'],
        ];

        $k = 0;
        foreach ($diffResult->reverse()->details as $actual) {
            [$operation, $line, $string] = $expected[$k];

            expect($actual->operation)->toBe($operation);
            expect($actual->line)->toBe($line);
            expect($actual->string)->toBe($string);

            $k++;
        }
    });
});

describe('add', function () {
    it('adds diff operations to the details list', function () {
        $diffResult = new DiffResult;
        expect(count($diffResult->details))->toBe(0);

        $diffResult->add(Operation::Equal, 1, 'line1');
        expect(count($diffResult->details))->toBe(1);

        $diffResult->add(Operation::Insert, 2, 'line2');
        expect(count($diffResult->details))->toBe(2);

        $diffResult->add(Operation::Delete, 3, 'line3');
        expect(count($diffResult->details))->toBe(3);
    });
});

describe('toUnifiedString', function () {
    it('converts diff operations to a unified string format', function () {
        $diffResult = new DiffResult;

        $diffResult->add(Operation::Equal, 1, 'line1');
        $diffResult->add(Operation::Insert, 2, 'line2');
        $diffResult->add(Operation::Delete, 3, 'line3');

        $actual = $diffResult->toUnifiedString();

        expect($actual)->toBe("line1\n+ line2\n- line3");
    });
});
