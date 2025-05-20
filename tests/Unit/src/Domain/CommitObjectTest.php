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
            ObjectHash $treeHash,
            GitSignature $author,
            GitSignature $committer,
            string $message,
            ?ObjectHash $parentHash,
            GitObjectHeader $header,
            string $expectedBody,
            string $expectedData,
            string $expectedPrettyPrint,
        ) {
            $actual = CommitObject::new($treeHash, $author, $committer, $message, $parentHash);

            expect($actual->header)->toEqual($header);
            expect($actual->body)->toBe($expectedBody);
            expect($actual->data)->toBe($expectedData);
            expect($actual->prettyPrint())->toBe($expectedPrettyPrint);
        }
    )
        ->with([
            'parent is null' => [
                'treeHash' => ObjectHash::parse('829c3804401b0727f70f73d4415e162400cbe57b'),
                'author' => GitSignature::new('Dummy Author', 'author@dummy.d', Timestamp::parse(1234567890, '+0900')),
                'committer' => GitSignature::new('Dummy Committer', 'committer@dummy.d', Timestamp::parse(1234567890, '+0900')),
                'message' => 'dummy message',
                'parentHash' => null,
                'header' => GitObjectHeader::new(ObjectType::Commit, 178),
                // expected
                'expectedBody' => "tree 829c3804401b0727f70f73d4415e162400cbe57b\nauthor Dummy Author <author@dummy.d> 1234567890 +0900\ncommitter Dummy Committer <committer@dummy.d> 1234567890 +0900\n\ndummy message\n",
                'expectedData' => "commit 178\0tree 829c3804401b0727f70f73d4415e162400cbe57b\nauthor Dummy Author <author@dummy.d> 1234567890 +0900\ncommitter Dummy Committer <committer@dummy.d> 1234567890 +0900\n\ndummy message\n",
                'expectedPrettyPrint' => "tree 829c3804401b0727f70f73d4415e162400cbe57b\nauthor Dummy Author <author@dummy.d> 1234567890 +0900\ncommitter Dummy Committer <committer@dummy.d> 1234567890 +0900\n\ndummy message\n",
            ],
            'parent is hash' => [
                'treeHash' => ObjectHash::parse('b133e1d259ac0205cca40b8de0fe8728e8789f9d'),
                'author' => GitSignature::new('Dummy Author', 'author@dummy.d', Timestamp::parse(1234567890, '+0900')),
                'committer' => GitSignature::new('Dummy Committer', 'committer@dummy.d', Timestamp::parse(1234567890, '+0900')),
                'message' => 'dummy message2',
                'parentHash' => ObjectHash::parse('0dbfdcf3970a6ea0761850575dcf5458451c7cde'),
                'header' => GitObjectHeader::new(ObjectType::Commit, 227),
                // expected
                'expectedBody' => "tree b133e1d259ac0205cca40b8de0fe8728e8789f9d\nparent 0dbfdcf3970a6ea0761850575dcf5458451c7cde\nauthor Dummy Author <author@dummy.d> 1234567890 +0900\ncommitter Dummy Committer <committer@dummy.d> 1234567890 +0900\n\ndummy message2\n",
                'expectedData' => "commit 227\0tree b133e1d259ac0205cca40b8de0fe8728e8789f9d\nparent 0dbfdcf3970a6ea0761850575dcf5458451c7cde\nauthor Dummy Author <author@dummy.d> 1234567890 +0900\ncommitter Dummy Committer <committer@dummy.d> 1234567890 +0900\n\ndummy message2\n",
                'expectedPrettyPrint' => "tree b133e1d259ac0205cca40b8de0fe8728e8789f9d\nparent 0dbfdcf3970a6ea0761850575dcf5458451c7cde\nauthor Dummy Author <author@dummy.d> 1234567890 +0900\ncommitter Dummy Committer <committer@dummy.d> 1234567890 +0900\n\ndummy message2\n",
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
