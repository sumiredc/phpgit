<?php

declare(strict_types=1);

use Phpgit\Domain\CommandInput\GitUpdateIndexOptionAction;
use Phpgit\Domain\FileStat;
use Phpgit\Domain\GitFileMode;
use Phpgit\Domain\GitIndex;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Domain\Repository\IndexRepositoryInterface;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Domain\TrackingFile;
use Phpgit\Lib\IOInterface;
use Phpgit\UseCase\GitUpdateIndexUseCase;
use Tests\Factory\FileStatFactory;
use Tests\Factory\ObjectHashFactory;

beforeEach(function () {
    $this->io = Mockery::mock(IOInterface::class);
    $this->objectRepository = Mockery::mock(ObjectRepositoryInterface::class);
    $this->fileRepository = Mockery::mock(FileRepositoryInterface::class);
    $this->indexRepository = Mockery::mock(IndexRepositoryInterface::class);
});

describe('__invoke -> actionAdd', function () {
    it('should returns success when exists object', function (
        string $file,
        string $content,
        FileStat $fileStat
    ) {
        $this->fileRepository->shouldReceive('exists')->andReturn(true);
        $this->fileRepository->shouldReceive('getContents')->andReturn($content);
        $this->objectRepository->shouldReceive('exists')->andReturn(true)->once();
        $this->objectRepository->shouldReceive('save')->never();
        $this->fileRepository->shouldReceive('getStat')->andReturn($fileStat)->once();
        $this->indexRepository->shouldReceive('getOrCreate')->andReturn(GitIndex::new())->once();
        $this->indexRepository->shouldReceive('save')->once();

        $useCase = new GitUpdateIndexUseCase(
            $this->io,
            $this->objectRepository,
            $this->fileRepository,
            $this->indexRepository
        );
        $actual = $useCase(GitUpdateIndexOptionAction::Add, $file, null, null);

        expect($actual)->toBe(Result::Success);
    })
        ->with([
            ['README.md', "# README\ndescription", FileStat::newForCacheinfo(33188)]
        ]);

    it('should returns success and save object when don\'t exists object', function (
        string $file,
        string $content,
        string $hash,
        FileStat $fileStat
    ) {
        $this->fileRepository->shouldReceive('exists')->andReturn(true); # in service
        $this->fileRepository->shouldReceive('getContents')->andReturn($content); # in service
        $this->objectRepository->shouldReceive('exists')->andReturn(false)->once();
        $this->objectRepository->shouldReceive('save')->andReturn(ObjectHash::parse($hash))->once();
        $this->fileRepository->shouldReceive('getStat')->andReturn($fileStat)->once();
        $this->indexRepository->shouldReceive('getOrCreate')->andReturn(GitIndex::new())->once();
        $this->indexRepository->shouldReceive('save')->once();

        $useCase = new GitUpdateIndexUseCase(
            $this->io,
            $this->objectRepository,
            $this->fileRepository,
            $this->indexRepository
        );
        $actual = $useCase(GitUpdateIndexOptionAction::Add, $file, null, null);

        expect($actual)->toBe(Result::Success);
    })
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
        function (
            string $file,
            array $expected
        ) {
            $this->fileRepository->shouldReceive('exists')->andReturn(false); # in service
            $this->io->shouldReceive('writeln')
                ->with(Mockery::on(function (array $actual) use ($expected) {
                    expect($actual)->toEqual($expected);
                    return true;
                }))
                ->once();

            $useCase = new GitUpdateIndexUseCase(
                $this->io,
                $this->objectRepository,
                $this->fileRepository,
                $this->indexRepository
            );
            $actual = $useCase(GitUpdateIndexOptionAction::Add, $file, null, null);

            expect($actual)->toBe(Result::GitError);
        }
    )
        ->with([
            [
                'file' => 'README.md',
                'expected' => [
                    'error: README.md: does not exist and --remove not passed',
                    'fatal: Unable to process path README.md'
                ]
            ]
        ]);

    it('should returns error and outputs stack trace when throws RuntimeException', function (
        string $file,
        string $content,
        Throwable $expected
    ) {
        $this->fileRepository->shouldReceive('exists')->andReturn(true); # in service
        $this->fileRepository->shouldReceive('getContents')->andReturn($content); # in service
        $this->objectRepository->shouldReceive('exists')->andReturn(true)->once();
        $this->fileRepository->shouldReceive('getStat')->andThrow(new RuntimeException('failed to get stat: /full/path'))->once();
        $this->indexRepository->shouldReceive('getOrCreate')->never();
        $this->io->shouldReceive('stackTrace')
            ->with(Mockery::on(function (Throwable $actual) use ($expected) {
                expect($actual)->toEqual($expected);
                return true;
            }))
            ->once();

        $useCase = new GitUpdateIndexUseCase(
            $this->io,
            $this->objectRepository,
            $this->fileRepository,
            $this->indexRepository
        );
        $actual = $useCase(GitUpdateIndexOptionAction::Add, $file, null, null);

        expect($actual)->toBe(Result::GitError);
    })
        ->with([
            [
                'README.md',
                "# README\ndescription",
                new RuntimeException('failed to get stat: /full/path')
            ]
        ]);
});

describe('__invoke -> actionRemove', function () {
    it('should returns success when exists object', function (string $file) {
        $entry = IndexEntry::new(
            FileStat::newForCacheinfo(33180), # dummy
            ObjectHash::new('dummy object'), # dummy
            TrackingFile::new($file)
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

        $useCase = new GitUpdateIndexUseCase(
            $this->io,
            $this->objectRepository,
            $this->fileRepository,
            $this->indexRepository
        );
        $actual = $useCase(GitUpdateIndexOptionAction::Remove, $file, null, null);

        expect($actual)->toBe(Result::Success);
    })
        ->with([
            ['README.md'],
        ]);

    it('should returns success and save object when don\'t exists object', function (string $file) {
        $entry = IndexEntry::new(
            FileStatFactory::new(),
            ObjectHashFactory::new(),
            TrackingFile::new($file)
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

        $useCase = new GitUpdateIndexUseCase(
            $this->io,
            $this->objectRepository,
            $this->fileRepository,
            $this->indexRepository
        );
        $actual = $useCase(GitUpdateIndexOptionAction::Remove, $file, null, null);

        expect($actual)->toBe(Result::Success);
    })
        ->with([
            ['README.md'],
        ]);;

    it(
        'should returns success and call method by actionForceRemove when don\'t exists file',
        function (string $file) {
            $this->fileRepository->shouldReceive('existsbyFilename')->andReturn(false)->once();
            $this->indexRepository->shouldReceive('exists')->andReturn(true);
            $this->indexRepository->shouldReceive('get')->andReturn(GitIndex::new())->once();
            $this->indexRepository->shouldReceive('save')->once();

            $useCase = new GitUpdateIndexUseCase(
                $this->io,
                $this->objectRepository,
                $this->fileRepository,
                $this->indexRepository
            );
            $actual = $useCase(GitUpdateIndexOptionAction::Remove, $file, null, null);

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            ['README.md']
        ]);

    it(
        'should returns error and outputs cannot add message, when don\'t exists index',
        function (string $file, array $expected) {
            $this->fileRepository->shouldReceive('existsbyFilename')->andReturn(true)->once();
            $this->indexRepository->shouldReceive('exists')->andReturn(false)->once();
            $this->io->shouldReceive('writeln')
                ->with(Mockery::on(function (array $actual) use ($expected) {
                    expect($actual)->toEqual($expected);
                    return true;
                }))
                ->once();

            $useCase = new GitUpdateIndexUseCase(
                $this->io,
                $this->objectRepository,
                $this->fileRepository,
                $this->indexRepository
            );
            $actual = $useCase(GitUpdateIndexOptionAction::Remove, $file, null, null);

            expect($actual)->toBe(Result::GitError);
        }
    )
        ->with([
            [
                'file' => 'src/main.rs',
                'expected' => [
                    'error: src/main.rs: cannot add to the index - missing --add option?',
                    'fatal: Unable to process path src/main.rs',
                ]
            ]
        ]);

    it(
        'should returns error and outputs cannot add message, when don\'t exists entry in index',
        function (string $file, array $expected) {
            $this->fileRepository->shouldReceive('existsbyFilename')->andReturn(true)->once();
            $this->indexRepository->shouldReceive('exists')->andReturn(true)->once();
            $this->indexRepository->shouldReceive('get')->andReturn(GitIndex::new())->once();
            $this->io->shouldReceive('writeln')
                ->with(Mockery::on(function (array $actual) use ($expected) {
                    expect($actual)->toEqual($expected);
                    return true;
                }))
                ->once();

            $useCase = new GitUpdateIndexUseCase(
                $this->io,
                $this->objectRepository,
                $this->fileRepository,
                $this->indexRepository
            );
            $actual = $useCase(GitUpdateIndexOptionAction::Remove, $file, null, null);

            expect($actual)->toBe(Result::GitError);
        }
    )
        ->with([
            [
                'file' => 'app/Controller/Controller.php',
                'expected' => [
                    'error: app/Controller/Controller.php: cannot add to the index - missing --add option?',
                    'fatal: Unable to process path app/Controller/Controller.php',
                ]
            ]
        ]);

    it(
        'should returns error when throws RuntimeException because fileStat is null',
        function (string $file, Throwable $expected) {
            $entry = IndexEntry::new(
                FileStat::newForCacheinfo(33180), # dummy
                ObjectHash::new('dummy object'), # dummy
                TrackingFile::new($file)
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

            $this->io->shouldReceive('stackTrace')
                ->with(Mockery::on(function (Throwable $actual) use ($expected) {
                    expect($actual)->toEqual($expected);
                    return true;
                }))
                ->once();

            $useCase = new GitUpdateIndexUseCase(
                $this->io,
                $this->objectRepository,
                $this->fileRepository,
                $this->indexRepository
            );
            $actual = $useCase(GitUpdateIndexOptionAction::Remove, $file, null, null);

            expect($actual)->toBe(Result::GitError);
        }
    )
        ->with([
            ['README.md', new RuntimeException('failed to get stat: /full/path')]
        ]);
});

describe('__invoke -> actionForceRemove', function () {
    it('should returns success when exists file', function (string $file) {
        $this->indexRepository->shouldReceive('exists')->andReturn(true);
        $this->indexRepository->shouldReceive('get')->andReturn(GitIndex::new())->once();
        $this->indexRepository->shouldReceive('save')->once();

        $useCase = new GitUpdateIndexUseCase(
            $this->io,
            $this->objectRepository,
            $this->fileRepository,
            $this->indexRepository
        );
        $actual = $useCase(GitUpdateIndexOptionAction::ForceRemove, $file, null, null);

        expect($actual)->toBe(Result::Success);
    })
        ->with([
            ['README.md']
        ]);

    it('should returns success when don\'t exists file', function (string $file) {
        $this->indexRepository->shouldReceive('exists')->andReturn(false);
        $this->indexRepository->shouldReceive('get')->never();

        $useCase = new GitUpdateIndexUseCase(
            $this->io,
            $this->objectRepository,
            $this->fileRepository,
            $this->indexRepository
        );
        $actual = $useCase(GitUpdateIndexOptionAction::ForceRemove, $file, null, null);

        expect($actual)->toBe(Result::Success);
    })
        ->with([
            ['README.md']
        ]);
});

describe('__invoke -> actionCacheinfo', function () {
    it('should returns success', function (
        string $file,
        GitFileMode $gitFileMode,
        ObjectHash $objectHash,
    ) {
        $this->fileRepository->shouldReceive('existsByFilename')->andReturn(true)->once();
        $this->indexRepository->shouldReceive('getOrCreate')->andReturn(GitIndex::new())->once();
        $this->indexRepository->shouldReceive('save')
            ->with(Mockery::on(function (GitIndex $actual) use ($file, $gitFileMode, $objectHash) {
                expect($actual->existsEntryByFilename($file))->toBeTrue();

                $entry = $actual->entries[$file];
                expect($entry->gitFileMode->value)->toBe($gitFileMode->value);
                expect($entry->objectHash->value)->toBe($objectHash->value);

                return true;
            }))
            ->once();

        $useCase = new GitUpdateIndexUseCase(
            $this->io,
            $this->objectRepository,
            $this->fileRepository,
            $this->indexRepository
        );
        $actual = $useCase(GitUpdateIndexOptionAction::Cacheinfo, $file, $gitFileMode, $objectHash);

        expect($actual)->toBe(Result::Success);
    })
        ->with([
            [
                'README.md',
                GitFileMode::DefaultFile,
                ObjectHash::parse('91d4a6610e67a14af17e800c2049b6b0a01162ef')
            ],
            [
                'src/main.rs',
                GitFileMode::ExeFile,
                ObjectHash::parse('4b569f42a6967dec04275af54f4ca9ab6a4eee64')
            ]
        ]);

    it('should returns error and output fatal message, when don\'t exists file', function (
        string $file,
        array $expected
    ) {
        $this->fileRepository->shouldReceive('existsByFilename')->andReturn(false)->once();
        $this->io->shouldReceive('writeln')
            ->with(Mockery::on(function (array $actual) use ($expected) {
                expect($actual)->toEqual($expected);
                return true;
            }))
            ->once();

        $useCase = new GitUpdateIndexUseCase(
            $this->io,
            $this->objectRepository,
            $this->fileRepository,
            $this->indexRepository
        );
        $actual = $useCase(
            GitUpdateIndexOptionAction::Cacheinfo,
            $file,
            GitFileMode::DefaultFile,
            ObjectHash::parse('91d4a6610e67a14af17e800c2049b6b0a01162ef')
        );

        expect($actual)->toBe(Result::GitError);
    })
        ->with([
            [
                'file' => 'README.md',
                'expected' => [
                    'error: README.md: cannot add to the index - missing --add option?',
                    'fatal: git update-index: --cacheinfo cannot add README.md',
                ]
            ]
        ]);

    it('should returns error and outputs stack trace, when gitFileMode or objectHash is null', function (
        string $file,
        ?GitFileMode $gitFileMode,
        ?ObjectHash $objectHash,
        Throwable $expected
    ) {
        $this->io->shouldReceive('stackTrace')
            ->with(Mockery::on(function (Throwable $actual) use ($expected) {
                expect($actual)->toEqual($expected);
                return true;
            }))
            ->once();

        $useCase = new GitUpdateIndexUseCase(
            $this->io,
            $this->objectRepository,
            $this->fileRepository,
            $this->indexRepository
        );
        $actual = $useCase(GitUpdateIndexOptionAction::Cacheinfo, $file, $gitFileMode, $objectHash);

        expect($actual)->toBe(Result::GitError);
    })
        ->with([
            'gitFileMode is null' => [
                'README.md',
                null,
                ObjectHash::new('dummy contents'),
                new InvalidArgumentException('invalid because gitFileMode in args is null')
            ],
            'objectHash is null' => [
                'src/main.rs',
                GitFileMode::DefaultFile,
                null,
                new InvalidArgumentException('invalid because objectHash in args is null')
            ],
        ]);
});
