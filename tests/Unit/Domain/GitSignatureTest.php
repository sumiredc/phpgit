<?php

declare(strict_types=1);

use Phpgit\Domain\GitSignature;
use Phpgit\Domain\Timestamp;

describe('new', function () {
    it(
        'should match to args to properties',
        function (string $name, string $email, Timestamp $timestamp) {
            $actual = GitSignature::new($name, $email, $timestamp);

            expect($actual->name)->toBe($name);
            expect($actual->email)->toBe($email);
            expect($actual->timestamp)->toEqual($timestamp);
        }
    )
        ->with([
            [
                'sample name',
                'sample@example.com',
                Timestamp::new(),
            ]
        ]);
});


describe('toRawString', function () {
    it(
        'should return to string by signature format',
        function (string $name, string $email, Timestamp $timestamp, string $expected) {
            $signature = GitSignature::new($name, $email, $timestamp);

            $actual = $signature->toRawString();

            expect($actual)->toBe($expected);
        }
    )
        ->with([
            [
                'sample name',
                'sample@example.com',
                Timestamp::parse(1746326253, '+0900'),
                'sample name <sample@example.com> 1746326253 +0900'
            ]
        ]);
});
