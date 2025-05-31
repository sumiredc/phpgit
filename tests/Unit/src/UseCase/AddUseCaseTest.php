<?php

declare(strict_types=1);

use Phpgit\Command\CommandInterface;
use Phpgit\Domain\BlobObject;
use Phpgit\Domain\FileStat;
use Phpgit\Domain\GitIndex;
use Phpgit\Domain\HashMap;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\PathType;
use Phpgit\Domain\Printer\PrinterInterface;
use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Domain\Repository\IndexRepositoryInterface;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Domain\TrackedPath;
use Phpgit\Request\AddRequest;
use Phpgit\Service\GetPathTypeServiceInterface;
use Phpgit\Service\StagedEntriesByPathServiceInterface;
use Phpgit\UseCase\AddUseCase;
use Symfony\Component\Console\Input\InputInterface;
use Tests\Factory\FileStatFactory;
use Tests\Factory\GitIndexFactory;
use Tests\Factory\ObjectHashFactory;

beforeEach(function () {
    $command = Mockery::mock(CommandInterface::class);
    $command->shouldReceive('addArgument')->shouldReceive('addOption');
    AddRequest::setUp($command);

    $this->input = Mockery::mock(InputInterface::class);
    $this->printer = Mockery::mock(PrinterInterface::class);
    $this->fileRepository = Mockery::mock(FileRepositoryInterface::class);
    $this->objectRepository = Mockery::mock(ObjectRepositoryInterface::class);
    $this->indexRepository = Mockery::mock(IndexRepositoryInterface::class);
    $this->getPathTypeService = Mockery::mock(GetPathTypeServiceInterface::class);
    $this->stagedEntriesByPathService = Mockery::mock(StagedEntriesByPathServiceInterface::class);
});

describe('__invoke', function () {
    it(
        'returns to success and a file add staging',
        function (
            string $path,
            PathType $pathType,
            HashMap $targets,
            HashMap $stagedEntries,
            array $resolvedTargets,
            array $gitIndexEntryPaths,
        ) {
            $this->input
                ->shouldReceive('getOption')->with('all')->andReturn(false)
                ->shouldReceive('getOption')->with('update')->andReturn(false)
                ->shouldReceive('getArgument')->with('path')->andReturn($path);

            $index = GitIndexFactory::new();
            $specifiedPath = TrackedPath::parse($path);

            $this->indexRepository->shouldReceive('getOrCreate')->andReturn($index)->once();
            $this->getPathTypeService->shouldReceive('__invoke')
                ->withArgs(expectEqualArg($index, $specifiedPath))
                ->andReturn($pathType)
                ->once();
            $this->fileRepository->shouldReceive('search')
                ->withArgs(expectEqualArg($specifiedPath))
                ->andReturn($targets)
                ->once();
            $this->stagedEntriesByPathService->shouldReceive('__invoke')
                ->withArgs(expectEqualArg($index, $specifiedPath, $pathType))
                ->andReturn($stagedEntries)
                ->once();

            /** 
             * @var TrackedPath $trackedPath
             * @var string $contents
             * @var bool $exists
             * @var FileStat $fileStat
             */
            foreach ($resolvedTargets as list($trackedPath, $contents, $isExists, $fileStat)) {
                $objectHash = ObjectHash::new(BlobObject::new($contents)->data);

                $this->fileRepository->shouldReceive('getContents')->withArgs(expectEqualArg($trackedPath))->andReturn($contents)->once();
                $this->objectRepository->shouldReceive('exists')->withArgs(expectEqualArg($objectHash))->andReturn($isExists)->once();
                if (!$isExists) {
                    $this->objectRepository->shouldReceive('save')->andReturn($objectHash)->once();
                }
                $this->fileRepository->shouldReceive('getStat')->withArgs(expectEqualArg($trackedPath))->andReturn($fileStat)->once();
            }

            $this->indexRepository->shouldReceive('save')
                ->withArgs(function (GitIndex $gitIndex) use ($gitIndexEntryPaths) {
                    $paths = [];
                    foreach ($gitIndex->entries as $entry) {
                        $paths[] = $entry->trackedPath->value;
                    }

                    expect($paths)->toEqual($gitIndexEntryPaths);

                    return true;
                })
                ->andReturn()
                ->once();

            $request = AddRequest::new($this->input);
            $useCase = new AddUseCase(
                $this->printer,
                $this->fileRepository,
                $this->objectRepository,
                $this->indexRepository,
                $this->getPathTypeService,
                $this->stagedEntriesByPathService
            );

            $result = $useCase($request);

            expect($result)->toBe(Result::Success);
        }
    )
        ->with([
            fn() => [
                'path' => 'src/main.go',
                'pathType' => PathType::File,
                'targets' => HashMap::parse([
                    'src/main.go' => TrackedPath::parse('src/main.go')
                ]),
                'stagedEntries' => HashMap::parse([
                    'src/main.go' => IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHashFactory::new(),
                        TrackedPath::parse('src/main.go'),
                    )
                ]),
                'resolvedTargets' => [
                    [
                        TrackedPath::parse('src/main.go'),
                        "package main\n\nfunc main() {}",
                        false,
                        FileStatFactory::new(),
                    ]
                ],
                'gitIndexEntryPaths' => ['src/main.go'],
            ],
            fn() => [
                'path' => 'README.md',
                'pathType' => PathType::File,
                'targets' => HashMap::parse([
                    'src/main.go' => TrackedPath::parse('README.md')
                ]),
                'stagedEntries' => HashMap::parse([
                    'README.md' => IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHashFactory::new(),
                        TrackedPath::parse('README.md'),
                    )
                ]),
                'resolvedTargets' => [
                    [
                        TrackedPath::parse('README.md'),
                        "# How To Use",
                        true,
                        FileStatFactory::new(),
                    ]
                ],
                'gitIndexEntryPaths' => ['README.md'],
            ],
        ]);

    it(
        'returns to success and all files add staging',
        function (
            PathType $pathType,
            HashMap $targets,
            HashMap $stagedEntries,
            array $resolvedTargets,
            array $gitIndexEntryPaths,
        ) {
            $this->input
                ->shouldReceive('getOption')->with('all')->andReturn(true)
                ->shouldReceive('getOption')->with('update')->andReturn(false)
                ->shouldReceive('getArgument')->with('path')->andReturn('');

            $index = GitIndexFactory::new();
            $specifiedPath = TrackedPath::parse(F_GIT_TRACKING_ROOT);

            $this->indexRepository->shouldReceive('getOrCreate')->andReturn($index)->once();
            $this->getPathTypeService->shouldReceive('__invoke')
                ->withArgs(expectEqualArg($index, $specifiedPath))
                ->andReturn($pathType)
                ->once();
            $this->fileRepository->shouldReceive('search')
                ->withArgs(expectEqualArg($specifiedPath))
                ->andReturn($targets)
                ->once();
            $this->stagedEntriesByPathService->shouldReceive('__invoke')
                ->withArgs(expectEqualArg($index, $specifiedPath, $pathType))
                ->andReturn($stagedEntries)
                ->once();

            /** 
             * @var TrackedPath $trackedPath
             * @var string $contents
             * @var bool $exists
             * @var FileStat $fileStat
             */
            foreach ($resolvedTargets as list($trackedPath, $contents, $isExists, $fileStat)) {
                $objectHash = ObjectHash::new(BlobObject::new($contents)->data);

                $this->fileRepository->shouldReceive('getContents')->withArgs(expectEqualArg($trackedPath))->andReturn($contents)->once();
                $this->objectRepository->shouldReceive('exists')->withArgs(expectEqualArg($objectHash))->andReturn($isExists)->once();
                if (!$isExists) {
                    $this->objectRepository->shouldReceive('save')->andReturn($objectHash)->once();
                }
                $this->fileRepository->shouldReceive('getStat')->withArgs(expectEqualArg($trackedPath))->andReturn($fileStat)->once();
            }

            $this->indexRepository->shouldReceive('save')
                ->withArgs(function (GitIndex $gitIndex) use ($gitIndexEntryPaths) {
                    $paths = [];
                    foreach ($gitIndex->entries as $entry) {
                        $paths[] = $entry->trackedPath->value;
                    }

                    expect($paths)->toEqual($gitIndexEntryPaths);

                    return true;
                })
                ->andReturn()
                ->once();

            $request = AddRequest::new($this->input);
            $useCase = new AddUseCase(
                $this->printer,
                $this->fileRepository,
                $this->objectRepository,
                $this->indexRepository,
                $this->getPathTypeService,
                $this->stagedEntriesByPathService
            );

            $result = $useCase($request);

            expect($result)->toBe(Result::Success);
        }
    )
        ->with([
            fn() => [
                'pathType' => PathType::Directory,
                'targets' => HashMap::parse([
                    'src/main.go' => TrackedPath::parse('src/main.go'),
                    'src/http.go' => TrackedPath::parse('src/http.go'),
                    'README.md' => TrackedPath::parse('README.md')
                ]),
                'stagedEntries' => HashMap::parse([
                    'src/main.go' => IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHashFactory::new(),
                        TrackedPath::parse('src/main.go'),
                    ),
                    'src/http.go' => IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHashFactory::new(),
                        TrackedPath::parse('src/http.go'),
                    ),
                    'README.md' => IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHashFactory::new(),
                        TrackedPath::parse('README.md'),
                    ),
                ]),
                'resolvedTargets' => [
                    [
                        TrackedPath::parse('src/main.go'),
                        "package main\n\nfunc main() {}",
                        false,
                        FileStatFactory::new(),
                    ],
                    [
                        TrackedPath::parse('src/http.go'),
                        "package main\n\nfunc handle() {}",
                        true,
                        FileStatFactory::new(),
                    ],
                    [
                        TrackedPath::parse('README.md'),
                        "# How To Use",
                        true,
                        FileStatFactory::new(),
                    ]
                ],
                'gitIndexEntryPaths' => ['README.md', 'src/http.go', 'src/main.go'],
            ],
        ]);

    it(
        'returns to success, on stages new, modified, and removed files',
        function (
            string $path,
            PathType $pathType,
            HashMap $targets,
            HashMap $stagedEntries,
            array $updateEntries,
            array $gitIndexEntryPaths
        ) {
            $this->input
                ->shouldReceive('getOption')->with('all')->andReturn(false)
                ->shouldReceive('getOption')->with('update')->andReturn(true)
                ->shouldReceive('getArgument')->with('path')->andReturn($path);

            $index = GitIndexFactory::new();
            $specifiedPath = TrackedPath::parse($path);
            $this->indexRepository->shouldReceive('getOrCreate')->andReturn($index)->once();
            $this->getPathTypeService->shouldReceive('__invoke')
                ->withArgs(expectEqualArg($index, $specifiedPath))->andReturn($pathType)->once();
            $this->fileRepository->shouldReceive('search')
                ->withArgs(expectEqualArg($specifiedPath, $pathType))->andReturn($targets)->once();
            $this->stagedEntriesByPathService->shouldReceive('__invoke')
                ->withArgs(expectEqualArg($index, $specifiedPath, $pathType))->andReturn($stagedEntries)->once();

            /** 
             * @var TrackedPath $trackedPath
             * @var string $contents
             * @var bool $exists
             * @var FileStat $fileStat
             */
            foreach ($updateEntries as list($trackedPath, $contents, $isExists, $fileStat)) {
                $objectHash = ObjectHash::new(BlobObject::new($contents)->data);

                $this->fileRepository->shouldReceive('getContents')->withArgs(expectEqualArg($trackedPath))->andReturn($contents)->once();
                $this->objectRepository->shouldReceive('exists')->withArgs(expectEqualArg($objectHash))->andReturn($isExists)->once();
                if (!$isExists) {
                    $this->objectRepository->shouldReceive('save')->andReturn($objectHash)->once();
                }
                $this->fileRepository->shouldReceive('getStat')->withArgs(expectEqualArg($trackedPath))->andReturn($fileStat)->once();
            }

            $this->indexRepository->shouldReceive('save')
                ->withArgs(function (GitIndex $gitIndex) use ($gitIndexEntryPaths) {
                    $paths = [];
                    foreach ($gitIndex->entries as $entry) {
                        $paths[] = $entry->trackedPath->value;
                    }

                    expect($paths)->toEqual($gitIndexEntryPaths);

                    return true;
                })
                ->andReturn()
                ->once();

            $request = AddRequest::new($this->input);
            $useCase = new AddUseCase(
                $this->printer,
                $this->fileRepository,
                $this->objectRepository,
                $this->indexRepository,
                $this->getPathTypeService,
                $this->stagedEntriesByPathService
            );

            $result = $useCase($request);

            expect($result)->toBe(Result::Success);
        }
    )
        ->with([
            'add entry in index' => fn() => [
                'path' => 'src/main.go',
                'pathType' => PathType::File,
                'targets' => HashMap::parse([
                    'src/main.go' => 'src/main.go'
                ]),
                'stagedEntries' => HashMap::parse([
                    'src/main.go' => IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHashFactory::new(),
                        TrackedPath::parse('src/main.go')
                    )
                ]),
                'updateEntries' => [
                    [
                        TrackedPath::parse('src/main.go'),
                        "package main\n\nfunc main() {}",
                        true,
                        FileStatFactory::new(),
                    ]
                ],
                'gitIndexEntryPaths' => ['src/main.go'],
            ],

            'update index' => fn() => [
                'path' => 'src',
                'pathType' => PathType::Directory,
                'targets' => HashMap::parse([
                    'src/new.go' => 'src/new.go',
                    'src/update.go' => 'src/update.go',
                ]),
                'stagedEntries' => HashMap::parse([
                    'src/delete.go' => IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHashFactory::new(),
                        TrackedPath::parse('src/delete.go')
                    ),
                    'src/update.go' => IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHashFactory::new(),
                        TrackedPath::parse('src/update.go')
                    ),
                ]),
                'updateEntries' => [
                    [
                        TrackedPath::parse('src/update.go'),
                        "package main\n\nfunc update() {}",
                        false,
                        FileStatFactory::new(),
                    ],
                ],
                'gitIndexEntryPaths' => [
                    'src/update.go',
                ],
            ],
        ]);

    it(
        'throws an exception and outputs fatal message, on not found target paths',
        function (string $path, string $expected) {
            $this->input
                ->shouldReceive('getOption')->with('all')->andReturn(false)
                ->shouldReceive('getOption')->with('update')->andReturn(false)
                ->shouldReceive('getArgument')->with('path')->andReturn($path);

            $this->printer->shouldReceive('writeln')->with($expected)->once();

            $request = AddRequest::new($this->input);
            $useCase = new AddUseCase(
                $this->printer,
                $this->fileRepository,
                $this->objectRepository,
                $this->indexRepository,
                $this->getPathTypeService,
                $this->stagedEntriesByPathService
            );

            $result = $useCase($request);

            expect($result)->toBe(Result::GitError);
        }
    )
        ->with([
            ['/outside/path', 'fatal: /outside/path: \'/outside/path\' is outside repository at \'/tmp/project\'']
        ]);

    it(
        'throws an exception and outputs fatal message, on did not match any files',
        function (string $path, string $expected) {
            $this->input
                ->shouldReceive('getOption')->with('all')->andReturn(false)
                ->shouldReceive('getOption')->with('update')->andReturn(false)
                ->shouldReceive('getArgument')->with('path')->andReturn($path);

            $index = GitIndexFactory::new();
            $this->indexRepository->shouldReceive('getOrCreate')->andReturn($index)->once();
            $this->getPathTypeService->shouldReceive('__invoke')->andReturn(PathType::File)->once();
            $this->fileRepository->shouldReceive('search')->andReturn(HashMap::new())->once();
            $this->stagedEntriesByPathService->shouldReceive('__invoke')->andReturn(HashMap::new())->once();

            $this->printer->shouldReceive('writeln')->with($expected)->once();

            $request = AddRequest::new($this->input);
            $useCase = new AddUseCase(
                $this->printer,
                $this->fileRepository,
                $this->objectRepository,
                $this->indexRepository,
                $this->getPathTypeService,
                $this->stagedEntriesByPathService
            );

            $result = $useCase($request);

            expect($result)->toBe(Result::GitError);
        }
    )
        ->with([
            ['not/match/path', 'fatal: pathspec \'not/match/path\' did not match any files']
        ]);

    it(
        'throws an exception and output stack trace, on unexpected exceptions',
        function (Throwable $th, Throwable $expected) {
            $this->input
                ->shouldReceive('getOption')->with('all')->andReturn(true)
                ->shouldReceive('getOption')->with('update')->andReturn(false)
                ->shouldReceive('getArgument')->with('path')->andReturn('');

            $this->indexRepository->shouldReceive('getOrCreate')->andThrow($th)->once();
            $this->printer->shouldReceive('stackTrace')->withArgs(expectEqualArg($expected))->once();

            $request = AddRequest::new($this->input);
            $useCase = new AddUseCase(
                $this->printer,
                $this->fileRepository,
                $this->objectRepository,
                $this->indexRepository,
                $this->getPathTypeService,
                $this->stagedEntriesByPathService
            );

            $result = $useCase($request);

            expect($result)->toBe(Result::InternalError);
        }
    )
        ->with([
            fn() => [new RuntimeException('unexpected error'), new RuntimeException('unexpected error')],
        ]);
});
