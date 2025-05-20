<?php

declare(strict_types=1);

use Phpgit\Command\CommandInterface;
use Phpgit\Domain\FileStat;
use Phpgit\Domain\GitIndex;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Domain\Repository\IndexRepositoryInterface;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Domain\TrackingPath;
use Phpgit\Domain\Printer\PrinterInterface;
use Phpgit\Request\UpdateIndexRequest;
use Phpgit\UseCase\UpdateIndexUseCase;
use Symfony\Component\Console\Input\InputInterface;
use Tests\Factory\FileStatFactory;
use Tests\Factory\ObjectHashFactory;

beforeEach(function () {
    $this->input = Mockery::mock(InputInterface::class);
    $this->printer = Mockery::mock(PrinterInterface::class);
    $this->objectRepository = Mockery::mock(ObjectRepositoryInterface::class);
    $this->fileRepository = Mockery::mock(FileRepositoryInterface::class);
    $this->indexRepository = Mockery::mock(IndexRepositoryInterface::class);

    $command = Mockery::mock(CommandInterface::class);
    $command->shouldReceive(['addArgument' => $command, 'addOption' => $command]);
    UpdateIndexRequest::setUp($command);
});

describe('__invoke -> actionAdd', function () {
    it(
        'returns success when exists object',
        function (string $file, string $content, FileStat $fileStat) {
            $this->input
                ->shouldReceive('getOption')->with('add')->andReturn(true)
                ->shouldReceive('getOption')->with('remove')->andReturn(false)
                ->shouldReceive('getOption')->with('force-remove')->andReturn(false)
                ->shouldReceive('getOption')->with('cacheinfo')->andReturn(false)
                ->shouldReceive('getArgument')->with('arg1')->andReturn($file);

            $this->fileRepository
                ->shouldReceive('exists')->andReturn(true)->once()
                ->shouldReceive('getContents')->andReturn($content)->once();
            $this->objectRepository
                ->shouldReceive('exists')->andReturn(true)->once()
                ->shouldReceive('save')->never();
            $this->fileRepository->shouldReceive('getStat')->andReturn($fileStat)->once();
            $this->indexRepository
                ->shouldReceive('getOrCreate')->andReturn(GitIndex::new())->once()
                ->shouldReceive('save')->once();

            $request = UpdateIndexRequest::new($this->input);
            $useCase = new UpdateIndexUseCase(
                $this->printer,
                $this->objectRepository,
                $this->fileRepository,
                $this->indexRepository
            );
            $actual = $useCase($request);

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            ['README.md', "# README\ndescription", FileStat::newForCacheinfo(33188)]
        ]);

    it(
        'should returns success and save object when don\'t exists object',
        function (string $file, string $content, string $hash, FileStat $fileStat) {
            $this->input->shouldReceive('getOption')->with('add')->andReturn(true);
            $this->input->shouldReceive('getOption')->with('remove')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('force-remove')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('cacheinfo')->andReturn(false);
            $this->input->shouldReceive('getArgument')->with('arg1')->andReturn($file); // file

            $this->fileRepository->shouldReceive('exists')->andReturn(true); # in service
            $this->fileRepository->shouldReceive('getContents')->andReturn($content); # in service
            $this->objectRepository->shouldReceive('exists')->andReturn(false)->once();
            $this->objectRepository->shouldReceive('save')->andReturn(ObjectHash::parse($hash))->once();
            $this->fileRepository->shouldReceive('getStat')->andReturn($fileStat)->once();
            $this->indexRepository->shouldReceive('getOrCreate')->andReturn(GitIndex::new())->once();
            $this->indexRepository->shouldReceive('save')->once();

            $request = UpdateIndexRequest::new($this->input);
            $useCase = new UpdateIndexUseCase(
                $this->printer,
                $this->objectRepository,
                $this->fileRepository,
                $this->indexRepository
            );
            $actual = $useCase($request);

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            [
                'file' => 'README.md',
                'content' => "# README\ndescription",
                'hash' => "91d4a6610e67a14af17e800c2049b6b0a01162ef",
                'fileStat' => FileStat::newForCacheinfo(33188)
            ]
        ]);

    it(
        'should returns error and outputs does not exists message when throws FileNotFoundException',
        function (string $file, string $expected) {
            $this->input->shouldReceive('getOption')->with('add')->andReturn(true);
            $this->input->shouldReceive('getOption')->with('remove')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('force-remove')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('cacheinfo')->andReturn(false);
            $this->input->shouldReceive('getArgument')->with('arg1')->andReturn($file); // file

            $this->fileRepository->shouldReceive('exists')->andReturn(false); # in service
            $this->printer->shouldReceive('writeln')->with($expected)->once();

            $request = UpdateIndexRequest::new($this->input);
            $useCase = new UpdateIndexUseCase(
                $this->printer,
                $this->objectRepository,
                $this->fileRepository,
                $this->indexRepository
            );
            $actual = $useCase($request);

            expect($actual)->toBe(Result::GitError);
        }
    )
        ->with([
            [
                'file' => 'README.md',
                'expected' => "error: README.md: does not exist and --remove not passed\nfatal: Unable to process path README.md"
            ]
        ]);

    it(
        'should returns error and outputs stack trace when throws RuntimeException',
        function (string $file, string $content, Throwable $expected) {
            $this->input->shouldReceive('getOption')->with('add')->andReturn(true);
            $this->input->shouldReceive('getOption')->with('remove')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('force-remove')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('cacheinfo')->andReturn(false);
            $this->input->shouldReceive('getArgument')->with('arg1')->andReturn($file); // file

            $this->fileRepository->shouldReceive('exists')->andReturn(true); # in service
            $this->fileRepository->shouldReceive('getContents')->andReturn($content); # in service
            $this->objectRepository->shouldReceive('exists')->andReturn(true)->once();
            $this->fileRepository->shouldReceive('getStat')->andThrow(new RuntimeException('failed to get stat: /full/path'))->once();
            $this->indexRepository->shouldReceive('getOrCreate')->never();
            $this->printer->shouldReceive('stackTrace')->withArgs(expectEqualArg($expected))->once();

            $request = UpdateIndexRequest::new($this->input);
            $useCase = new UpdateIndexUseCase(
                $this->printer,
                $this->objectRepository,
                $this->fileRepository,
                $this->indexRepository
            );
            $actual = $useCase($request);

            expect($actual)->toBe(Result::InternalError);
        }
    )
        ->with([
            [
                'README.md',
                "# README\ndescription",
                new RuntimeException('failed to get stat: /full/path')
            ]
        ]);
});

describe('__invoke -> actionRemove', function () {
    it(
        'should returns success when exists object',
        function (string $file) {
            $this->input->shouldReceive('getOption')->with('add')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('remove')->andReturn(true);
            $this->input->shouldReceive('getOption')->with('force-remove')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('cacheinfo')->andReturn(false);
            $this->input->shouldReceive('getArgument')->with('arg1')->andReturn($file); // file

            $entry = IndexEntry::new(
                FileStatFactory::new(),
                ObjectHashFactory::new(),
                TrackingPath::new($file)
            );
            $index = GitIndex::new();
            $index->addEntry($entry);

            $this->fileRepository->shouldReceive('existsbyFilename')->andReturn(true)->once();
            $this->indexRepository->shouldReceive('exists')->andReturn(true)->once();
            $this->indexRepository->shouldReceive('get')->andReturn($index)->once();
            $this->fileRepository->shouldReceive('exists')->andReturn(true); # in service
            $this->fileRepository->shouldReceive('getContents')->andReturn('dummy contents'); # in service
            $this->objectRepository->shouldReceive('exists')->andReturn(true)->once();
            $this->objectRepository->shouldReceive('save')->never();
            $this->fileRepository->shouldReceive('getStat')->andReturn(FileStatFactory::new())->once();
            $this->indexRepository->shouldReceive('save')->once();

            $request = UpdateIndexRequest::new($this->input);
            $useCase = new UpdateIndexUseCase(
                $this->printer,
                $this->objectRepository,
                $this->fileRepository,
                $this->indexRepository
            );
            $actual = $useCase($request);

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            ['README.md'],
        ]);

    it(
        'should returns success and save object when don\'t exists object',
        function (string $file) {
            $this->input->shouldReceive('getOption')->with('add')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('remove')->andReturn(true);
            $this->input->shouldReceive('getOption')->with('force-remove')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('cacheinfo')->andReturn(false);
            $this->input->shouldReceive('getArgument')->with('arg1')->andReturn($file); // file

            $entry = IndexEntry::new(
                FileStatFactory::new(),
                ObjectHashFactory::new(),
                TrackingPath::new($file)
            );
            $index = GitIndex::new();
            $index->addEntry($entry);

            $this->fileRepository->shouldReceive('existsbyFilename')->andReturn(true)->once();
            $this->indexRepository->shouldReceive('exists')->andReturn(true)->once();
            $this->indexRepository->shouldReceive('get')->andReturn($index)->once();
            $this->fileRepository->shouldReceive('exists')->andReturn(true); # in service
            $this->fileRepository->shouldReceive('getContents')->andReturn('dummy contents'); # in service
            $this->objectRepository->shouldReceive('exists')->andReturn(false)->once();
            $this->objectRepository->shouldReceive('save')->andReturn(ObjectHash::new('dummy object'))->once();
            $this->fileRepository->shouldReceive('getStat')->andReturn(FileStatFactory::new())->once();
            $this->indexRepository->shouldReceive('save')->once();

            $request = UpdateIndexRequest::new($this->input);
            $useCase = new UpdateIndexUseCase(
                $this->printer,
                $this->objectRepository,
                $this->fileRepository,
                $this->indexRepository
            );
            $actual = $useCase($request);

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            ['README.md'],
        ]);;

    it(
        'should returns success and call method by actionForceRemove when don\'t exists file',
        function (string $file) {
            $this->input->shouldReceive('getOption')->with('add')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('remove')->andReturn(true);
            $this->input->shouldReceive('getOption')->with('force-remove')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('cacheinfo')->andReturn(false);
            $this->input->shouldReceive('getArgument')->with('arg1')->andReturn($file); // file

            $this->fileRepository->shouldReceive('existsbyFilename')->andReturn(false)->once();
            $this->indexRepository->shouldReceive('exists')->andReturn(true);
            $this->indexRepository->shouldReceive('get')->andReturn(GitIndex::new())->once();
            $this->indexRepository->shouldReceive('save')->once();

            $request = UpdateIndexRequest::new($this->input);
            $useCase = new UpdateIndexUseCase(
                $this->printer,
                $this->objectRepository,
                $this->fileRepository,
                $this->indexRepository
            );
            $actual = $useCase($request);

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            ['README.md']
        ]);

    it(
        'should returns error and outputs cannot add message, when don\'t exists index',
        function (string $file, string $expected) {
            $this->input->shouldReceive('getOption')->with('add')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('remove')->andReturn(true);
            $this->input->shouldReceive('getOption')->with('force-remove')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('cacheinfo')->andReturn(false);
            $this->input->shouldReceive('getArgument')->with('arg1')->andReturn($file); // file

            $this->fileRepository->shouldReceive('existsbyFilename')->andReturn(true)->once();
            $this->indexRepository->shouldReceive('exists')->andReturn(false)->once();
            $this->printer->shouldReceive('writeln')->withArgs(expectEqualArg($expected))->once();

            $request = UpdateIndexRequest::new($this->input);
            $useCase = new UpdateIndexUseCase(
                $this->printer,
                $this->objectRepository,
                $this->fileRepository,
                $this->indexRepository
            );
            $actual = $useCase($request);

            expect($actual)->toBe(Result::GitError);
        }
    )
        ->with([
            [
                'file' => 'src/main.rs',
                'expected' => "error: src/main.rs: cannot add to the index - missing --add option?\nfatal: Unable to process path src/main.rs"
            ]
        ]);

    it(
        'should returns error and outputs cannot add message, when don\'t exists entry in index',
        function (string $file, string $expected) {
            $this->input->shouldReceive('getOption')->with('add')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('remove')->andReturn(true);
            $this->input->shouldReceive('getOption')->with('force-remove')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('cacheinfo')->andReturn(false);
            $this->input->shouldReceive('getArgument')->with('arg1')->andReturn($file); // file

            $this->fileRepository->shouldReceive('existsbyFilename')->andReturn(true)->once();
            $this->indexRepository->shouldReceive('exists')->andReturn(true)->once();
            $this->indexRepository->shouldReceive('get')->andReturn(GitIndex::new())->once();
            $this->printer->shouldReceive('writeln')->with($expected)->once();

            $request = UpdateIndexRequest::new($this->input);
            $useCase = new UpdateIndexUseCase(
                $this->printer,
                $this->objectRepository,
                $this->fileRepository,
                $this->indexRepository
            );
            $actual = $useCase($request);

            expect($actual)->toBe(Result::GitError);
        }
    )
        ->with([
            [
                'file' => 'app/Controller/Controller.php',
                'expected' => "error: app/Controller/Controller.php: cannot add to the index - missing --add option?\nfatal: Unable to process path app/Controller/Controller.php"
            ]
        ]);

    it(
        'should returns error when throws RuntimeException because fileStat is null',
        function (string $file, Throwable $expected) {
            $this->input->shouldReceive('getOption')->with('add')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('remove')->andReturn(true);
            $this->input->shouldReceive('getOption')->with('force-remove')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('cacheinfo')->andReturn(false);
            $this->input->shouldReceive('getArgument')->with('arg1')->andReturn($file); // file

            $entry = IndexEntry::new(
                FileStat::newForCacheinfo(33180), # dummy
                ObjectHash::new('dummy object'), # dummy
                TrackingPath::new($file)
            );
            $index = GitIndex::new();
            $index->addEntry($entry);

            $this->fileRepository->shouldReceive('existsbyFilename')->andReturn(true)->once();
            $this->indexRepository->shouldReceive('exists')->andReturn(true)->once();
            $this->indexRepository->shouldReceive('get')->andReturn($index)->once();
            $this->fileRepository->shouldReceive('exists')->andReturn(true); # in service
            $this->fileRepository->shouldReceive('getContents')->andReturn('dummy contents'); # in service
            $this->objectRepository->shouldReceive('exists')->andReturn(true)->once();
            $this->fileRepository->shouldReceive('getStat')->andThrow(new RuntimeException('failed to get stat: /full/path'))->once();
            $this->printer->shouldReceive('stackTrace')->withArgs(expectEqualArg($expected))->once();

            $request = UpdateIndexRequest::new($this->input);
            $useCase = new UpdateIndexUseCase(
                $this->printer,
                $this->objectRepository,
                $this->fileRepository,
                $this->indexRepository
            );
            $actual = $useCase($request);

            expect($actual)->toBe(Result::InternalError);
        }
    )
        ->with([
            ['README.md', new RuntimeException('failed to get stat: /full/path')]
        ]);
});

describe('__invoke -> actionForceRemove', function () {
    it(
        'should returns success when exists file',
        function (string $file) {
            $this->input->shouldReceive('getOption')->with('add')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('remove')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('force-remove')->andReturn(true);
            $this->input->shouldReceive('getOption')->with('cacheinfo')->andReturn(false);
            $this->input->shouldReceive('getArgument')->with('arg1')->andReturn($file); // file

            $this->indexRepository->shouldReceive('exists')->andReturn(true);
            $this->indexRepository->shouldReceive('get')->andReturn(GitIndex::new())->once();
            $this->indexRepository->shouldReceive('save')->once();

            $request = UpdateIndexRequest::new($this->input);
            $useCase = new UpdateIndexUseCase(
                $this->printer,
                $this->objectRepository,
                $this->fileRepository,
                $this->indexRepository
            );
            $actual = $useCase($request);

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            ['README.md']
        ]);

    it(
        'should returns success when don\'t exists file',
        function (string $file) {
            $this->input->shouldReceive('getOption')->with('add')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('remove')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('force-remove')->andReturn(true);
            $this->input->shouldReceive('getOption')->with('cacheinfo')->andReturn(false);
            $this->input->shouldReceive('getArgument')->with('arg1')->andReturn($file); // file

            $this->indexRepository->shouldReceive('exists')->andReturn(false);
            $this->indexRepository->shouldReceive('get')->never();

            $request = UpdateIndexRequest::new($this->input);
            $useCase = new UpdateIndexUseCase(
                $this->printer,
                $this->objectRepository,
                $this->fileRepository,
                $this->indexRepository
            );
            $actual = $useCase($request);

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            ['README.md']
        ]);
});

describe('__invoke -> actionCacheinfo', function () {
    it(
        'should returns success',
        function (string $file, string $mode, string $object) {
            $this->input->shouldReceive('getOption')->with('add')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('remove')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('force-remove')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('cacheinfo')->andReturn(true);
            $this->input->shouldReceive('getArgument')->with('arg1')->andReturn($mode);
            $this->input->shouldReceive('getArgument')->with('arg2')->andReturn($object);
            $this->input->shouldReceive('getArgument')->with('arg3')->andReturn($file);

            $this->fileRepository->shouldReceive('existsByFilename')->andReturn(true)->once();
            $this->indexRepository->shouldReceive('getOrCreate')->andReturn(GitIndex::new())->once();
            $this->indexRepository->shouldReceive('save')
                ->withArgs(function (GitIndex $actual) use ($file, $mode, $object) {
                    expect($actual->existsEntryByFilename($file))->toBeTrue();

                    $entry = $actual->entries[$file];
                    expect($entry->gitFileMode->value)->toBe($mode);
                    expect($entry->objectHash->value)->toBe($object);

                    return true;
                })
                ->once();

            $request = UpdateIndexRequest::new($this->input);
            $useCase = new UpdateIndexUseCase(
                $this->printer,
                $this->objectRepository,
                $this->fileRepository,
                $this->indexRepository
            );
            $actual = $useCase($request);

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            [
                'README.md',
                '100644',
                '91d4a6610e67a14af17e800c2049b6b0a01162ef',
            ],
            [
                'src/main.rs',
                '100755',
                '4b569f42a6967dec04275af54f4ca9ab6a4eee64',
            ]
        ]);

    it(
        'it throws an exception on given to invalid args and output fatal error',
        function (string $file, string $mode, string $object, string $expected) {
            $this->input->shouldReceive('getOption')->with('add')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('remove')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('force-remove')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('cacheinfo')->andReturn(true);
            $this->input->shouldReceive('getArgument')->with('arg1')->andReturn($mode);
            $this->input->shouldReceive('getArgument')->with('arg2')->andReturn($object);
            $this->input->shouldReceive('getArgument')->with('arg3')->andReturn($file);

            $this->printer->shouldReceive('writeln')->with($expected)->once();

            $request = UpdateIndexRequest::new($this->input);
            $useCase = new UpdateIndexUseCase(
                $this->printer,
                $this->objectRepository,
                $this->fileRepository,
                $this->indexRepository
            );
            $actual = $useCase($request);

            expect($actual)->toBe(Result::GitError);
        }
    )
        ->with([
            'mode is invalid value' => [
                'file' => 'README.md',
                'mode' => 'aaa',
                'object' => 'f8e79b0708e78c524f092ff6bc9a2f7bab70f006',
                'expected' => 'fatal: git update-index: --cacheinfo cannot add aaa',
            ],
            'object is not sha1' => [
                'file' => 'src/main.rs',
                'mode' => '100644',
                'object' => 'invalid-hash-string',
                'expected' => 'fatal: git update-index: --cacheinfo cannot add invalid-hash-string'
            ],
        ]);

    it(
        'should returns error and output fatal message, when don\'t exists file',
        function (string $file, string $expected) {
            $this->input->shouldReceive('getOption')->with('add')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('remove')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('force-remove')->andReturn(false);
            $this->input->shouldReceive('getOption')->with('cacheinfo')->andReturn(true);
            $this->input->shouldReceive('getArgument')->with('arg1')->andReturn('100644');
            $this->input->shouldReceive('getArgument')->with('arg2')->andReturn('91d4a6610e67a14af17e800c2049b6b0a01162ef');
            $this->input->shouldReceive('getArgument')->with('arg3')->andReturn($file);

            $this->fileRepository->shouldReceive('existsByFilename')->andReturn(false)->once();
            $this->printer->shouldReceive('writeln')->with($expected)->once();

            $request = UpdateIndexRequest::new($this->input);
            $useCase = new UpdateIndexUseCase(
                $this->printer,
                $this->objectRepository,
                $this->fileRepository,
                $this->indexRepository
            );
            $actual = $useCase($request);

            expect($actual)->toBe(Result::GitError);
        }
    )
        ->with([
            [
                'file' => 'README.md',
                'expected' => "error: README.md: cannot add to the index - missing --add option?\nfatal: git update-index: --cacheinfo cannot add README.md"
            ]
        ]);
});
