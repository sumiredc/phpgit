<?php

declare(strict_types=1);

use Phpgit\Domain\HeadType;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Reference;
use Phpgit\Domain\Repository\RefRepositoryInterface;
use Phpgit\Service\ResolveRevisionService;
use Tests\Factory\ReferenceFactory;

beforeEach(function () {
    $this->refRepository = Mockery::mock(RefRepositoryInterface::class);
});

describe('__invoke', function () {
    it(
        'returns to same arg on the arg specified sha1',
        function (string $rev, string $expected) {
            $service = new ResolveRevisionService($this->refRepository);
            $actual = $service($rev);

            expect($actual->value)->toBe($expected);
        }
    )
        ->with([
            ['03e404fd168941cfed98f46654680130dd85968b', '03e404fd168941cfed98f46654680130dd85968b']
        ]);

    it(
        'returns to result by resolveHead on the arg specified HEAD and HeadType is Hash',
        function (ObjectHash $objectHash, string $expected) {
            $this->refRepository
                ->shouldReceive('headType')->andReturn(HeadType::Hash)->once()
                ->shouldReceive('resolveHead')->andReturn($objectHash)->once();

            $service = new ResolveRevisionService($this->refRepository);
            $actual = $service('HEAD');

            expect($actual->value)->toBe($expected);
        }
    )
        ->with([
            [
                ObjectHash::parse('7138a51661947b19b5088da5a2bfede2876f49b9'),
                '7138a51661947b19b5088da5a2bfede2876f49b9',
            ]
        ]);

    it(
        'returns to result by head on the arg specified HEAD and HeadType is Reference',
        function (ObjectHash $objectHash, string $expected) {
            $ref = ReferenceFactory::local();

            $this->refRepository
                ->shouldReceive('headType')->andReturn(HeadType::Reference)->once()
                ->shouldReceive('head')->andReturn($ref)->once()
                ->shouldReceive('exists')->withArgs(expectEqualArg($ref))->andReturn(true)->once()
                ->shouldReceive('resolve')->withArgs(expectEqualArg($ref))->andReturn($objectHash)->once();

            $service = new ResolveRevisionService($this->refRepository);
            $actual = $service('HEAD');

            expect($actual->value)->toBe($expected);
        }
    )
        ->with([
            [
                ObjectHash::parse('7138a51661947b19b5088da5a2bfede2876f49b9'),
                '7138a51661947b19b5088da5a2bfede2876f49b9',
            ]
        ]);

    it(
        'returns to null by head on the arg specified HEAD and HeadType is Reference but does not exists reference',
        function () {
            $ref = ReferenceFactory::local();

            $this->refRepository
                ->shouldReceive('headType')->andReturn(HeadType::Reference)->once()
                ->shouldReceive('head')->andReturn($ref)->once()
                ->shouldReceive('exists')->withArgs(expectEqualArg($ref))->andReturn(false)->once();

            $service = new ResolveRevisionService($this->refRepository);
            $actual = $service('HEAD');

            expect($actual)->toBeNull();
        }
    );

    it(
        'returns to null by head on the arg specified HEAD and HeadType is Reference',
        function () {
            $this->refRepository
                ->shouldReceive('headType')->andReturn(HeadType::Reference)->once()
                ->shouldReceive('head')->andReturnNull()->once();

            $service = new ResolveRevisionService($this->refRepository);
            $actual = $service('HEAD');

            expect($actual)->toBeNull();
        }
    );

    it(
        'throws an exception on the args specified HEAD but HeadType is unknown',
        function () {
            $this->refRepository
                ->shouldReceive('headType')->andReturn(HeadType::Unknown)->once();

            $service = new ResolveRevisionService($this->refRepository);

            expect(fn() => $service('HEAD'))->toThrow(new LogicException('HEAD is Unknown'));
        }
    );

    it(
        'returns to result by resolve reference on the arg specified reference',
        function (string $rev, Reference $ref, ObjectHash $objectHash, string $expected) {
            $this->refRepository->shouldReceive([
                'exists' => true,
                'resolve' => $objectHash
            ])
                ->withArgs(expectEqualArg($ref))->once();

            $service = new ResolveRevisionService($this->refRepository);
            $actual = $service($rev);

            expect($actual->value)->toBe($expected);
        }
    )
        ->with([
            [
                'refs/heads/main',
                Reference::parse('refs/heads/main'),
                ObjectHash::parse('7138a51661947b19b5088da5a2bfede2876f49b9'),
                '7138a51661947b19b5088da5a2bfede2876f49b9',
            ]
        ]);

    it(
        'returns null on failed to tryParse in reference',
        function (string $rev) {
            $service = new ResolveRevisionService($this->refRepository);
            $actual = $service($rev);

            expect($actual)->toBeNull();
        }
    )
        ->with([
            ['not-reference']
        ]);

    it(
        'returns null on does not exists target ref',
        function (string $rev) {
            $this->refRepository->shouldReceive('exists')->andReturn(false)->once();

            $service = new ResolveRevisionService($this->refRepository);
            $actual = $service($rev);

            expect($actual)->toBeNull();
        }
    )
        ->with([
            ['refs/heads/dont-exists']
        ]);
});
