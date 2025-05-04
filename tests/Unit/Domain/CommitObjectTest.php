<?php

declare(strict_types=1);

use Phpgit\Domain\CommitObject;
use Phpgit\Domain\GitObjectHeader;
use Phpgit\Domain\GitSignature;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\ObjectType;
use Phpgit\Domain\Timestamp;

describe('new', function () {
    it(
        'should match to args to properties',
        function (
            ObjectHash $objectHash,
            GitSignature $author,
            GitSignature $committer,
            string $message,
            GitObjectHeader $header,
            string $body,
            string $expected
        ) {
            $actual = CommitObject::new($objectHash, $author, $committer, $message);

            expect($actual->header)->toEqual($header);
            expect($actual->body)->toBe($body);
            expect($actual->data)->toBe($expected);
        }
    )
        ->with([
            [
                'objectHash' => ObjectHash::parse('829c3804401b0727f70f73d4415e162400cbe57b'),
                'author' => GitSignature::new('Dummy Author', 'author@dummy.d', Timestamp::parse(1234567890, '+0900')),
                'committer' => GitSignature::new('Dummy Committer', 'committer@dummy.d', Timestamp::parse(1234567890, '+0900')),
                'message' => 'dummy message',
                'header' => GitObjectHeader::new(ObjectType::Commit, 178),
                'body' => "tree 829c3804401b0727f70f73d4415e162400cbe57b\nauthor Dummy Author <author@dummy.d> 1234567890 +0900\ncommitter Dummy Committer <committer@dummy.d> 1234567890 +0900\n\ndummy message\n",
                'expected' => "commit 178\0tree 829c3804401b0727f70f73d4415e162400cbe57b\nauthor Dummy Author <author@dummy.d> 1234567890 +0900\ncommitter Dummy Committer <committer@dummy.d> 1234567890 +0900\n\ndummy message\n"
            ]
        ]);
});

describe('parse', function () {
    it(
        'throws exception when given to different object type',
        function (string $blob, Throwable $expected) {
            expect(fn() => CommitObject::parse($blob))->toThrow($expected);
        }
    )
        ->with([
            [
                "blob 73\0" . 'package main

import "fmt"

func main() {
	fmt.Println("Hello, world")
}
',
                new UnexpectedValueException('unexpected ObjectType value: blob')
            ]
        ]);
});
