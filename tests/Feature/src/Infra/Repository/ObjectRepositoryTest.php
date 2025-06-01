<?php

declare(strict_types=1);

use Phpgit\Domain\CompressedPayload;
use Phpgit\Domain\GitObject;
use Phpgit\Domain\ObjectHash;
use Phpgit\Infra\Repository\ObjectRepository;
use Tests\CommandRunner;
use Tests\Factory\BlobObjectFactory;
use Tests\Factory\TreeObjectFactory;

beforeAll(function () {
    CommandRunner::run('php git init');
});

beforeEach(function () {
    refreshObjects();
    set_error_handler(fn() => true);
});

afterEach(function () {
    restore_error_handler();
});

describe('save', function () {
    it(
        'returns to hash on saves blob object',
        function () {
            $object = BlobObjectFactory::new();
            $hash = ObjectHash::new($object->data);

            $repository = new ObjectRepository;
            $actual = $repository->save($object);

            expect($actual->value)->toBe($hash->value);
        }
    );

    it(
        'returns to hash on saves tree object',
        function () {
            $object = TreeObjectFactory::new();
            $hash = ObjectHash::new($object->data);

            $repository = new ObjectRepository;
            $actual = $repository->save($object);

            expect($actual->value)->toBe($hash->value);
        }
    );

    it(
        'throws an exception, on fails to make object directory',
        function (GitObject $object, ObjectHash $hash, Throwable $expected) {
            touch(sprintf('%s/%s', F_GIT_OBJECTS_DIR, $hash->dir));
            $repository = new ObjectRepository;

            expect(fn() => $repository->save($object))->toThrow($expected);
        }
    )
        ->with([
            function () {
                $object = BlobObjectFactory::new();
                $hash = ObjectHash::new($object->data);
                $dir = sprintf('%s/%s', F_GIT_OBJECTS_DIR, $hash->dir);

                return [
                    $object,
                    $hash,
                    new RuntimeException("failed to mkdir: $dir")
                ];
            }
        ]);

    it(
        'throws an exception, on fails to save object',
        function (GitObject $object, ObjectHash $hash, Throwable $expected) {
            mkdir($hash->fullPath(), 0777, true);
            $repository = new ObjectRepository;

            expect(fn() => $repository->save($object))->toThrow($expected);
        }
    )
        ->with([
            function () {
                $object = BlobObjectFactory::new();
                $hash = ObjectHash::new($object->data);
                $path = $hash->fullPath();

                return [
                    $object,
                    $hash,
                    new RuntimeException("failed to file_put_contents: $path")
                ];
            }
        ]);
});

describe('getCompressedPayload', function () {
    it(
        'returns to compressed payload on exists blob object',
        function () {
            $object = BlobObjectFactory::new();
            $compressed = CompressedPayload::fromOriginal($object->data);

            $repository = new ObjectRepository;
            $hash = $repository->save($object);

            $actual = $repository->getCompressedPayload($hash);

            expect($actual->value)->toBe($compressed->value);
        }
    );

    it(
        'returns to compressed payload on exists tree object',
        function () {
            $object = TreeObjectFactory::new();
            $compressed = CompressedPayload::fromOriginal($object->data);

            $repository = new ObjectRepository;
            $hash = $repository->save($object);

            $actual = $repository->getCompressedPayload($hash);

            expect($actual->value)->toBe($compressed->value);
        }
    );

    it(
        'throws an exception on does not exists objects',
        function (ObjectHash $objectHash, Throwable $expected) {
            $repository = new ObjectRepository;

            expect(fn() => $repository->getCompressedPayload($objectHash))->toThrow($expected);
        }
    )
        ->with([
            [
                ObjectHash::new('not-exists'),
                new RuntimeException('failed to file_get_contents: /tmp/project/.git/objects/66/3fe234e4c3e5bd2ff2aa75f302ac8ec26581b2')
            ]
        ]);
});

describe('get', function () {
    it(
        'returns to object on exists blob object',
        function () {
            $object = BlobObjectFactory::new();

            $repository = new ObjectRepository;
            $hash = $repository->save($object);

            $actual = $repository->get($hash);

            expect($actual->data)->toBe($object->data);
        }
    );

    it(
        'returns to object on exists tree object',
        function () {
            $object = TreeObjectFactory::new();

            $repository = new ObjectRepository;
            $hash = $repository->save($object);

            $actual = $repository->get($hash);

            expect($actual->data)->toBe($object->data);
        }
    );
});

describe('exists', function () {
    it(
        'returns true on exists object file',
        function () {
            $repository = new ObjectRepository;

            $object = BlobObjectFactory::new();
            $objectHash = $repository->save($object);

            expect($repository->exists($objectHash))->toBeTrue();
        }
    );

    it(
        'returns false on does not exists object file',
        function () {
            $repository = new ObjectRepository;

            $objectHash = ObjectHash::new('not-exists');
            expect($repository->exists($objectHash))->toBeFalse();
        }
    );
});
