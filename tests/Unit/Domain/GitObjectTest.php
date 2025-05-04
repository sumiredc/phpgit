<?php

declare(strict_types=1);

use Phpgit\Domain\BlobObject;
use Phpgit\Domain\CommitObject;
use Phpgit\Domain\GitObject;
use Phpgit\Domain\ObjectType;
use Phpgit\Domain\TreeObject;

describe('parse', function () {
    it('should initializes object', function (
        string $object,
        string $objectClass,
        ObjectType $objectType,
        int $size,
        string $body,
    ) {
        $actual = GitObject::parse($object);

        expect($actual::class)->toBe($objectClass);
        expect($actual->objectType)->toBe($objectType);
        expect($actual->size)->toBe($size);
        expect($actual->body)->toBe($body);
        expect($actual->data)->toBe($object);
    })
        ->with([
            'blob' => [
                "blob 73\0" . 'package main

import "fmt"

func main() {
	fmt.Println("Hello, world")
}
',
                BlobObject::class,
                ObjectType::Blob,
                73,
                'package main

import "fmt"

func main() {
	fmt.Println("Hello, world")
}
'
            ],
            'tree' => [
                "tree 67\0" . "100644 blob 59e0b395a6ee16f4673442df6f59d4be1f0daea6\tREADME.md\n040000 tree 59e0b395a6ee16f4673442df6f59d4be1f0daea6\tsrc\n",
                TreeObject::class,
                ObjectType::Tree,
                67,
                "100644 blob 59e0b395a6ee16f4673442df6f59d4be1f0daea6\tREADME.md\n040000 tree 59e0b395a6ee16f4673442df6f59d4be1f0daea6\tsrc\n"
            ],
            'commit' => [
                "commit 178\0tree 829c3804401b0727f70f73d4415e162400cbe57b\nauthor Dummy Author <author@dummy.d> 1234567890 +0900\ncommitter Dummy Committer <committer@dummy.d> 1234567890 +0900\n\ndummy message\n",
                CommitObject::class,
                ObjectType::Commit,
                178,
                "tree 829c3804401b0727f70f73d4415e162400cbe57b\nauthor Dummy Author <author@dummy.d> 1234567890 +0900\ncommitter Dummy Committer <committer@dummy.d> 1234567890 +0900\n\ndummy message\n"
            ]
        ]);

    it('throws an exception when invalid header', function (string $object, Throwable $expected) {
        expect(fn() => GitObject::parse($object))->toThrow($expected);
    })
        ->with([
            'no header' => [
                "\0" . 'body string',
                new RuntimeException('failed to parse GitObject: header: , body: body string')
            ],
            'no null-terminated string, because unescaped' => [
                'blob 11\0body string',
                new RuntimeException('failed to parse GitObject: header: blob 11\0body string, body: '),
            ],
            'cannot get type' => [
                "tree0\0",
                new RuntimeException('failed to parse GitObject: type: tree0, size: ')
            ],
            'cannot get size, is null' => [
                "blob\0",
                new RuntimeException('failed to parse GitObject: type: blob, size: ')
            ],
            'cannot get size, is empty string' => [
                "tree \0",
                new RuntimeException('failed to parse GitObject: type: tree, size: ')
            ],
            'size is not number' => [
                "blob a\0a",
                new TypeError('size don\'t be number: a')
            ],
            'not allowed ObjectType' => [
                "image 0\0",
                new ValueError('"image" is not a valid backing value for enum Phpgit\Domain\ObjectType'),
            ],
        ]);
});
