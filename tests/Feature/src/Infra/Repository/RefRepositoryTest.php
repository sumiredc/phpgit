<?php

declare(strict_types=1);

use Phpgit\Domain\HeadType;
use Phpgit\Domain\Reference;
use Phpgit\Domain\ReferenceType;
use Phpgit\Infra\Repository\RefRepository;
use Tests\CommandRunner;
use Tests\Factory\ObjectHashFactory;

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

describe('exists', function () {
    it(
        'returns true on exists reference file',
        function () {
            $repository = new RefRepository;
            $ref = Reference::new(ReferenceType::Local, 'main');
            touch($ref->fullPath);

            $actual = $repository->exists($ref);

            expect($actual)->toBeTrue();
        }
    );

    it(
        'returns false on does not exists reference file',
        function () {
            $repository = new RefRepository;
            $ref = Reference::new(ReferenceType::Local, 'not-found');

            $actual = $repository->exists($ref);

            expect($actual)->toBeFalse();
        }
    );
});


describe('create', function () {
    it(
        'creates reference on does not exists reference',
        function () {
            $repository = new RefRepository;
            $ref = Reference::new(ReferenceType::Local, 'ref1');
            $hash = ObjectHashFactory::random();

            $repository->create($ref, $hash);

            expect($repository->exists($ref))->toBeTrue();
            expect($repository->resolve($ref))->toEqual($hash);
        }
    );

    it(
        'throws an exception on exists reference',
        function () {
            $repository = new RefRepository;
            $ref = Reference::new(ReferenceType::Local, 'ref2');
            $hash = ObjectHashFactory::random();
            touch($ref->fullPath);

            expect(fn() => $repository->create($ref, $hash))
                ->toThrow(new RuntimeException('Reference already exists: refs/heads/ref2'));
        }
    );
});

describe('update', function () {
    it(
        'updates reference on exists reference file',
        function () {
            $repository = new RefRepository;
            $ref = Reference::new(ReferenceType::Local, 'main');
            $hash = ObjectHashFactory::random();
            touch($ref->fullPath);

            $repository->update($ref, $hash);
            $actual = $repository->resolve($ref);

            expect($actual)->toEqual($hash);
        }
    );

    it(
        'throws an exception on does not exists reference',
        function () {
            $repository = new RefRepository;
            $ref = Reference::new(ReferenceType::Local, 'not-found');
            $hash = ObjectHashFactory::random();

            expect(fn() => $repository->update($ref, $hash))->toThrow(new RuntimeException('Reference not found: refs/heads/not-found'));
        }
    );
});

describe('updateHead', function () {
    it(
        'updates HEAD on exists reference file',
        function () {
            $repository = new RefRepository;
            $hash = ObjectHashFactory::random();

            $repository->updateHead($hash);
            $actual = $repository->resolveHead();

            expect($actual)->toEqual($hash);
        }
    );
});

describe('createOrUpdate', function () {
    it(
        'creates reference on does not exists reference',
        function () {
            $repository = new RefRepository;
            $ref = Reference::new(ReferenceType::Local, 'ref1');
            $hash = ObjectHashFactory::random();

            $repository->createOrUpdate($ref, $hash);

            expect($repository->exists($ref))->toBeTrue();
            expect($repository->resolve($ref))->toEqual($hash);
        }
    );

    it(
        'updates reference on exists reference',
        function () {
            $repository = new RefRepository;
            $ref = Reference::new(ReferenceType::Local, 'ref2');
            touch($ref->fullPath);
            $hash = ObjectHashFactory::random();

            $repository->createOrUpdate($ref, $hash);

            expect($repository->exists($ref))->toBeTrue();
            expect($repository->resolve($ref))->toEqual($hash);
        }
    );
});

describe('delete', function () {
    it(
        'creates reference on does not exists reference',
        function () {
            $repository = new RefRepository;
            $ref = Reference::new(ReferenceType::Local, 'main');
            touch($ref->fullPath);

            expect($repository->exists($ref))->toBeTrue();

            $repository->delete($ref);

            expect($repository->exists($ref))->toBeFalse();
        }
    );
});

describe('headType', function () {
    it(
        'returns head type is Hash',
        function () {
            $repository = new RefRepository;
            file_put_contents(F_GIT_HEAD, "86f7e437faa5a7fce15d1ddcb9eaeaea377667b8\n");

            $actual = $repository->headType();

            expect($actual)->toBe(HeadType::Hash);
        }
    );

    it(
        'returns head type is Reference',
        function () {
            $repository = new RefRepository;
            file_put_contents(F_GIT_HEAD, "ref: refs/heads/main\n");

            $actual = $repository->headType();

            expect($actual)->toBe(HeadType::Reference);
        }
    );

    it(
        'returns head type is Unknown on does not exists HEAD file',
        function () {
            $repository = new RefRepository;
            unlink(F_GIT_HEAD);

            $actual = $repository->headType();

            expect($actual)->toBe(HeadType::Unknown);
        }
    );

    it(
        'returns head type is Unknown on empty HEAD',
        function () {
            $repository = new RefRepository;
            file_put_contents(F_GIT_HEAD, '');

            $actual = $repository->headType();

            expect($actual)->toBe(HeadType::Unknown);
        }
    );

    it(
        'returns head type is Unknown on not hash and reference HEAD',
        function () {
            $repository = new RefRepository;
            file_put_contents(F_GIT_HEAD, 'dummy');

            $actual = $repository->headType();

            expect($actual)->toBe(HeadType::Unknown);
        }
    );
});

describe('head', function () {
    it(
        'returns null on head type is hash',
        function () {
            $repository = new RefRepository;
            file_put_contents(F_GIT_HEAD, "86f7e437faa5a7fce15d1ddcb9eaeaea377667b8\n");

            $actual = $repository->head();

            expect($actual)->toBeNull();
        }
    );

    it(
        'returns head type is Reference',
        function () {
            $repository = new RefRepository;
            $ref = Reference::new(ReferenceType::Local, 'main');
            file_put_contents(F_GIT_HEAD, "ref: refs/heads/main\n");

            $actual = $repository->head();

            expect($actual)->toEqual($ref);
        }
    );

    it(
        'throws an exception on head type is Unknown',
        function () {
            $repository = new RefRepository;
            file_put_contents(F_GIT_HEAD, 'unknown');

            expect(fn() => $repository->head())->toThrow(new RuntimeException('HEAD is Unknown'));
        }
    );
});

describe('resolve', function () {
    it(
        'returns hash in reference',
        function () {
            $repository = new RefRepository;
            $ref = Reference::new(ReferenceType::Local, 'main');
            file_put_contents($ref->fullPath, "829c3804401b0727f70f73d4415e162400cbe57b\n");

            $actual = $repository->resolve($ref);

            expect($actual->value)->toBe('829c3804401b0727f70f73d4415e162400cbe57b');
        }
    );

    it(
        'throws an exception on reference does not exists',
        function () {
            $repository = new RefRepository;
            $ref = Reference::new(ReferenceType::Local, 'not-found');

            expect(fn() => $repository->resolve($ref))
                ->toThrow(new RuntimeException('failed to fopen: refs/heads/not-found'));
        }
    );

    it(
        'throws an exception on reference is empty',
        function () {
            $repository = new RefRepository;
            $ref = Reference::new(ReferenceType::Local, 'main');
            file_put_contents($ref->fullPath, '');

            expect(fn() => $repository->resolve($ref))
                ->toThrow(new RuntimeException('failed to fgets: refs/heads/main'));
        }
    );
});

describe('resolveHead', function () {
    it(
        'returns hash in HEAD',
        function () {
            $repository = new RefRepository;
            $ref = Reference::new(ReferenceType::Local, 'main');
            file_put_contents(F_GIT_HEAD, "ref: {$ref->path}\n");
            file_put_contents($ref->fullPath, "b28b7af69320201d1cf206ebf28373980add1451\n");

            $actual = $repository->resolveHead();

            expect($actual->value)->toBe('b28b7af69320201d1cf206ebf28373980add1451');
        }
    );

    it(
        'returns hash in HEAD of reference',
        function () {
            $repository = new RefRepository;
            file_put_contents(F_GIT_HEAD, "829c3804401b0727f70f73d4415e162400cbe57b\n");

            $actual = $repository->resolveHead();

            expect($actual->value)->toBe('829c3804401b0727f70f73d4415e162400cbe57b');
        }
    );

    it(
        'throws an exception on reference does not exists',
        function () {
            $repository = new RefRepository;
            unlink(F_GIT_HEAD);

            expect(fn() => $repository->resolveHead())
                ->toThrow(new RuntimeException('failed to fopen by HEAD'));
        }
    );

    it(
        'throws an exception on reference is empty',
        function () {
            $repository = new RefRepository;
            file_put_contents(F_GIT_HEAD, '');

            expect(fn() => $repository->resolveHead())
                ->toThrow(new RuntimeException('failed to fgets by HEAD first line'));
        }
    );
});

describe('dereference', function () {
    it(
        'returns reference in HEAD',
        function () {
            $repository = new RefRepository;
            $ref = Reference::new(ReferenceType::Local, 'main');
            file_put_contents(F_GIT_HEAD, "ref: {$ref->path}\n");

            $actual = $repository->dereference(GIT_HEAD);

            expect($actual->path)->toBe('refs/heads/main');
        }
    );

    it(
        'returns reference on arg is reference',
        function () {
            $repository = new RefRepository;
            $ref = Reference::new(ReferenceType::Local, 'dev');

            $actual = $repository->dereference($ref->path);

            expect($actual->path)->toBe($ref->path);
        }
    );

    it(
        'returns null on arg does not reference',
        function () {
            $repository = new RefRepository;

            $actual = $repository->dereference('not-ref');

            expect($actual)->toBeNull();
        }
    );
});
