<?php

declare(strict_types=1);

use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\PathType;
use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Domain\TrackedPath;
use Phpgit\Service\GetPathTypeService;
use Tests\Factory\FileStatFactory;
use Tests\Factory\GitIndexFactory;
use Tests\Factory\ObjectHashFactory;

beforeEach(function () {
    $this->fileRepository = Mockery::mock(FileRepositoryInterface::class);
});

describe('__invoke', function () {
    it(
        'returns PathType::Directory on exists directory in storage by path',
        function (string $path) {
            $trackedPath = TrackedPath::parse($path);
            $this->fileRepository
                ->shouldReceive('existsDir')->withArgs(expectEqualArg($trackedPath))->andReturn(true)->once()
                ->shouldReceive('exists')->never();

            $service = new GetPathTypeService($this->fileRepository);
            $actual = $service(GitIndexFactory::new(), $trackedPath);

            expect($actual)->toBe(PathType::Directory);
        }
    )
        ->with([
            ['exists/directory']
        ]);

    it(
        'returns PathType::File on exists file in storage by path',
        function (string $path) {
            $trackedPath = TrackedPath::parse($path);
            $this->fileRepository
                ->shouldReceive('existsDir')->withArgs(expectEqualArg($trackedPath))->andReturn(false)->once()
                ->shouldReceive('exists')->withArgs(expectEqualArg($trackedPath))->andReturn(true)->once();

            $service = new GetPathTypeService($this->fileRepository);
            $actual = $service(GitIndexFactory::new(), $trackedPath);

            expect($actual)->toBe(PathType::File);
        }
    )
        ->with([
            ['exists/path']
        ]);

    it(
        'returns PathType::File, on does not exists file in storage but exists entry in index',
        function (string $path) {
            $trackedPath = TrackedPath::parse($path);
            $entry = IndexEntry::new(
                FileStatFactory::new(),
                ObjectHashFactory::new(),
                $trackedPath
            );
            $gitIndex = GitIndexFactory::new();
            $gitIndex->addEntry($entry);

            $this->fileRepository
                ->shouldReceive('existsDir')->withArgs(expectEqualArg($trackedPath))->andReturn(false)->once()
                ->shouldReceive('exists')->withArgs(expectEqualArg($trackedPath))->andReturn(false)->once();

            $service = new GetPathTypeService($this->fileRepository);
            $actual = $service($gitIndex, $trackedPath);

            expect($actual)->toBe(PathType::File);
        }
    )
        ->with([
            ['exists/entry']
        ]);

    it(
        'returns PathType::Pattern, on does not exists directory, file and entry',
        function (string $path) {
            $trackedPath = TrackedPath::parse($path);
            $gitIndex = GitIndexFactory::new();

            $this->fileRepository
                ->shouldReceive('existsDir')->withArgs(expectEqualArg($trackedPath))->andReturn(false)->once()
                ->shouldReceive('exists')->withArgs(expectEqualArg($trackedPath))->andReturn(false)->once();

            $service = new GetPathTypeService($this->fileRepository);
            $actual = $service($gitIndex, $trackedPath);

            expect($actual)->toBe(PathType::Pattern);
        }
    )
        ->with([
            ['pattern/*.go']
        ]);

    it(
        'returns PathType::Unknown, on does not exists directory, file, entry and does not match pattern',
        function (string $path) {
            $trackedPath = TrackedPath::parse($path);
            $gitIndex = GitIndexFactory::new();

            $this->fileRepository
                ->shouldReceive('existsDir')->withArgs(expectEqualArg($trackedPath))->andReturn(false)->once()
                ->shouldReceive('exists')->withArgs(expectEqualArg($trackedPath))->andReturn(false)->once();

            $service = new GetPathTypeService($this->fileRepository);
            $actual = $service($gitIndex, $trackedPath);

            expect($actual)->toBe(PathType::Unknown);
        }
    )
        ->with([
            ['not/pattern']
        ]);
});
