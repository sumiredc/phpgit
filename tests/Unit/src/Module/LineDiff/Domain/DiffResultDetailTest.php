<?php

declare(strict_types=1);

use Phpgit\Module\LineDiff\Domain\DiffResultDetail;
use Phpgit\Module\LineDiff\Domain\Operation;

describe('__construct', function () {
    it(
        'initializes properties with fiven arguments',
        function (
            Operation $operation,
            int $line,
            string $string,
        ) {
            $actual = new DiffResultDetail($operation, $line, $string);

            expect($actual->operation)->toBe($operation);
            expect($actual->line)->toBe($line);
            expect($actual->string)->toBe($string);
        }
    )
        ->with([
            [Operation::Delete, 1, 'delete line'],
            [Operation::Insert, 20, 'insert line'],
            [Operation::Equal, 300, 'same line'],
        ]);
});
