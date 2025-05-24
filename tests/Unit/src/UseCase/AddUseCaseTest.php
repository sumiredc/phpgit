<?php

declare(strict_types=1);

use Phpgit\Command\CommandInterface;
use Phpgit\Domain\BlobObject;
use Phpgit\Domain\FileStat;
use Phpgit\Domain\GitIndex;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Printer\PrinterInterface;
use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Domain\Repository\IndexRepositoryInterface;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Domain\TrackedPath;
use Phpgit\Request\AddRequest;
use Phpgit\UseCase\AddUseCase;
use Symfony\Component\Console\Input\InputInterface;
use Tests\Factory\FileStatFactory;
use Tests\Factory\GitIndexFactory;

beforeEach(function () {
    $command = Mockery::mock(CommandInterface::class);
    $command->shouldReceive('addArgument')->shouldReceive('addOption');
    AddRequest::setUp($command);

    $this->input = Mockery::mock(InputInterface::class);
    $this->printer = Mockery::mock(PrinterInterface::class);
    $this->fileRepository = Mockery::mock(FileRepositoryInterface::class);
    $this->objectRepository = Mockery::mock(ObjectRepositoryInterface::class);
    $this->indexRepository = Mockery::mock(IndexRepositoryInterface::class);
});

describe('__invoke', function () {
    it(
        'returns to success and files add staging',
        function (
            bool $all,
            string $path,
            array $targets,
            array $resolvedTargets,
            array $gitIndexEntryPaths,
        ) {
            $this->input
                ->shouldReceive('getOption')->with('all')->andReturn($all)
                ->shouldReceive('getArgument')->with('path')->andReturn($path);

            $this->fileRepository->shouldReceive('search')->withArgs(expectEqualArg(TrackedPath::parse($path)))->andReturn($targets)->once();
            $this->indexRepository->shouldReceive('getOrCreate')->andReturn(GitIndexFactory::new())->once();

            /** 
             * @var TrackedPath $trackedPath
             * @var string $contents
             * @var bool $exists
             * @var FileStat $fileStat
             */
            foreach ($resolvedTargets as list($trackedPath, $contents, $exists, $fileStat)) {
                $objectHash = ObjectHash::new(BlobObject::new($contents)->data);

                $this->fileRepository->shouldReceive('getContents')->withArgs(expectEqualArg($trackedPath))->andReturn($contents)->once();
                $this->objectRepository->shouldReceive('exists')->withArgs(expectEqualArg($objectHash))->andReturn($exists)->once();
                if (!$exists) {
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
            );

            $result = $useCase($request);

            expect($result)->toBe(Result::Success);
        }
    )
        ->with([
            fn() => [
                'all' => false,
                'path' => 'src/main.go',
                'targets' => [TrackedPath::parse('src/main.go')],
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
                'all' => true,
                'path' => '',
                'targets' => [
                    TrackedPath::parse('src/main.go'),
                    TrackedPath::parse('src/index.php')
                ],
                'resolvedTargets' => [
                    [
                        TrackedPath::parse('src/main.go'),
                        "package main\n\nfunc main() {}",
                        false,
                        FileStatFactory::new(),
                    ],
                    [
                        TrackedPath::parse('src/index.php'),
                        "<?php\necho \'Hello world!\';",
                        true,
                        FileStatFactory::new(),
                    ]
                ],
                'gitIndexEntryPaths' => [
                    'src/index.php',
                    'src/main.go',
                ],
            ]
        ]);

    it(
        'throws an exception and outputs fatal message, on failed path because out side repository',
        function (string $path, string $expected) {
            $this->input
                ->shouldReceive('getOption')->with('all')->andReturn(false)
                ->shouldReceive('getArgument')->with('path')->andReturn($path);

            $this->printer->shouldReceive('writeln')->with($expected)->once();

            $request = AddRequest::new($this->input);
            $useCase = new AddUseCase(
                $this->printer,
                $this->fileRepository,
                $this->objectRepository,
                $this->indexRepository,
            );

            $result = $useCase($request);

            expect($result)->toBe(Result::GitError);
        }
    )
        ->with([
            'relative path' => [
                '..',
                'fatal: ..: \'..\' is outside repository at \'/test/project\''
            ],
            'absolute path' => [
                '/var/www/html',
                'fatal: /var/www/html: \'/var/www/html\' is outside repository at \'/test/project\''
            ],
        ]);

    it(
        'throws an exception and outputs fatal message, on not found target paths',
        function (string $path, string $expected) {
            $this->input
                ->shouldReceive('getOption')->with('all')->andReturn(false)
                ->shouldReceive('getArgument')->with('path')->andReturn($path);

            $this->fileRepository->shouldReceive('search')->withArgs(expectEqualArg(TrackedPath::parse($path)))->andReturn([])->once();
            $this->printer->shouldReceive('writeln')->with($expected)->once();

            $request = AddRequest::new($this->input);
            $useCase = new AddUseCase(
                $this->printer,
                $this->fileRepository,
                $this->objectRepository,
                $this->indexRepository,
            );

            $result = $useCase($request);

            expect($result)->toBe(Result::GitError);
        }
    )
        ->with([
            ['not/found/path', 'fatal: pathspec \'not/found/path\' did not match any files']
        ]);

    it(
        'throws an exception and output stack trace, on unexpected exceptions',
        function (Throwable $th, Throwable $expected) {
            $this->input
                ->shouldReceive('getOption')->with('all')->andReturn(true)
                ->shouldReceive('getArgument')->with('path')->andReturn('');

            $this->fileRepository->shouldReceive('search')->andThrow($th)->once();
            $this->printer->shouldReceive('stackTrace')->withArgs(expectEqualArg($expected))->once();

            $request = AddRequest::new($this->input);
            $useCase = new AddUseCase(
                $this->printer,
                $this->fileRepository,
                $this->objectRepository,
                $this->indexRepository,
            );

            $result = $useCase($request);

            expect($result)->toBe(Result::InternalError);
        }
    )
        ->with([
            fn() => [new RuntimeException('unexpected error'), new RuntimeException('unexpected error')],
        ]);
});
