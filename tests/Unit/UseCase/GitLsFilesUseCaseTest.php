<?php

declare(strict_types=1);

use Phpgit\Domain\FileStat;
use Phpgit\Domain\GitIndex;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Repository\IndexRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Domain\TrackingFile;
use Phpgit\Lib\IOInterface;
use Phpgit\Request\GitLsFilesRequest;
use Phpgit\UseCase\GitLsFilesUseCase;
use Symfony\Component\Console\Input\InputInterface;
use Tests\Factory\FileStatFactory;
use Tests\Factory\ObjectHashFactory;

beforeEach(function () {
    $this->input = Mockery::mock(InputInterface::class);
    $this->io = Mockery::mock(IOInterface::class);
    $this->indexRepository = Mockery::mock(IndexRepositoryInterface::class);
});

describe('__invoke', function () {
    it('should returns success and don\'t output, when not exists index', function () {
        $this->input->shouldReceive('getOption')->andReturn(false)->times(4);

        $this->indexRepository->shouldReceive('exists')->andReturn(false)->once();
        $this->io->shouldReceive('writeln')->never();

        $request = GitLsFilesRequest::new($this->input);
        $useCase = new GitLsFilesUseCase($this->io, $this->indexRepository);
        $actual = $useCase($request);

        expect($actual)->toBe(Result::Success);
    });

    it('should returns error and outputs stack trace, when throws RuntimeException', function (Throwable $expected) {
        $this->input->shouldReceive('getOption')->andReturn(false)->times(4);

        $this->indexRepository->shouldReceive('exists')->andReturn(true)->once();
        $this->indexRepository->shouldReceive('get')->andThrow($expected)->once();

        $this->io->shouldReceive('stackTrace')
            ->withArgs(function (Throwable $actual) use ($expected) {
                expect($actual)->toEqual($expected);

                return true;
            })
            ->once();

        $request = GitLsFilesRequest::new($this->input);
        $useCase = new GitLsFilesUseCase($this->io, $this->indexRepository);
        $actual = $useCase($request);

        expect($actual)->toBe(Result::GitError);
    })
        ->with([
            [new RuntimeException('fails to look index')]
        ]);
});

describe('__invoke -> actionDefault', function () {
    it('should returns success and output path list', function (array $paths, array $expected) {
        $this->input->shouldReceive('getOption')->andReturn(false)->times(4);

        $entries = array_map(fn(string $path) => IndexEntry::new(
            FileStatFactory::new(),
            ObjectHashFactory::new(),
            TrackingFile::new($path),
        ), $paths);

        $index = GitIndex::new();
        foreach ($entries as $entry) {
            $index->addEntry($entry);
        }

        $this->indexRepository->shouldReceive('exists')->andReturn(true)->once();
        $this->indexRepository->shouldReceive('get')->andReturn($index)->once();
        $this->io->shouldReceive('writeln')
            ->withArgs(function (array $actual) use ($expected) {
                expect($actual)->toEqual($expected);

                return true;
            })
            ->once();

        $request = GitLsFilesRequest::new($this->input);
        $useCase = new GitLsFilesUseCase($this->io, $this->indexRepository);
        $actual = $useCase($request);

        expect($actual)->toBe(Result::Success);
    })
        ->with([
            [
                'paths' => [
                    'Makefile',
                    'handler.c',
                    'handler.h',
                    'html/errors/forbidden.html',
                    'html/errors/internal-server-error.html',
                    'html/errors/not-found.html',
                    'html/public/index.html',
                    'html/public/style.css',
                    'http/delete.http',
                    'http/get.http',
                    'http/patch.http',
                    'http/post.http',
                    'http/put.http',
                    'log/access.log',
                    'logger.c',
                    'main.c',
                    'utils.c',
                    'utils.h',
                ],
                'expected' => [
                    'Makefile' => 'Makefile',
                    'handler.c' => 'handler.c',
                    'handler.h' => 'handler.h',
                    'html/errors/forbidden.html' => 'html/errors/forbidden.html',
                    'html/errors/internal-server-error.html' => 'html/errors/internal-server-error.html',
                    'html/errors/not-found.html' => 'html/errors/not-found.html',
                    'html/public/index.html' => 'html/public/index.html',
                    'html/public/style.css' => 'html/public/style.css',
                    'http/delete.http' => 'http/delete.http',
                    'http/get.http' => 'http/get.http',
                    'http/patch.http' => 'http/patch.http',
                    'http/post.http' => 'http/post.http',
                    'http/put.http' => 'http/put.http',
                    'log/access.log' => 'log/access.log',
                    'logger.c' => 'logger.c',
                    'main.c' => 'main.c',
                    'utils.c' => 'utils.c',
                    'utils.h' => 'utils.h',
                ]
            ]
        ]);
});

describe('__invoke -> actionTag', function () {
    it('should returns success and output list', function (array $paths, array $expected) {
        $this->input->shouldReceive('getOption')->with('tag')->andReturn(true)->once();
        $this->input->shouldReceive('getOption')->with('zero')->andReturn(false)->once();
        $this->input->shouldReceive('getOption')->with('stage')->andReturn(false)->once();
        $this->input->shouldReceive('getOption')->with('debug')->andReturn(false)->once();

        $entries = array_map(fn(string $path) => IndexEntry::new(
            FileStatFactory::new(),
            ObjectHashFactory::new(),
            TrackingFile::new($path),
        ), $paths);

        $index = GitIndex::new();
        foreach ($entries as $entry) {
            $index->addEntry($entry);
        }

        $this->indexRepository->shouldReceive('exists')->andReturn(true)->once();
        $this->indexRepository->shouldReceive('get')->andReturn($index)->once();
        $this->io->shouldReceive('writeln')
            ->withArgs(function (array $actual) use ($expected) {
                expect($actual)->toEqual($expected);

                return true;
            })
            ->once();

        $request = GitLsFilesRequest::new($this->input);
        $useCase = new GitLsFilesUseCase($this->io, $this->indexRepository);
        $actual = $useCase($request);

        expect($actual)->toBe(Result::Success);
    })
        ->with([
            [
                'paths' => [
                    'Makefile',
                    'handler.c',
                    'handler.h',
                    'html/errors/forbidden.html',
                    'html/errors/internal-server-error.html',
                    'html/errors/not-found.html',
                    'html/public/index.html',
                    'html/public/style.css',
                    'http/delete.http',
                    'http/get.http',
                    'http/patch.http',
                    'http/post.http',
                    'http/put.http',
                    'log/access.log',
                    'logger.c',
                    'main.c',
                    'utils.c',
                    'utils.h',
                ],
                'expected' => [
                    'Makefile' => 'H Makefile',
                    'handler.c' => 'H handler.c',
                    'handler.h' => 'H handler.h',
                    'html/errors/forbidden.html' => 'H html/errors/forbidden.html',
                    'html/errors/internal-server-error.html' => 'H html/errors/internal-server-error.html',
                    'html/errors/not-found.html' => 'H html/errors/not-found.html',
                    'html/public/index.html' => 'H html/public/index.html',
                    'html/public/style.css' => 'H html/public/style.css',
                    'http/delete.http' => 'H http/delete.http',
                    'http/get.http' => 'H http/get.http',
                    'http/patch.http' => 'H http/patch.http',
                    'http/post.http' => 'H http/post.http',
                    'http/put.http' => 'H http/put.http',
                    'log/access.log' => 'H log/access.log',
                    'logger.c' => 'H logger.c',
                    'main.c' => 'H main.c',
                    'utils.c' => 'H utils.c',
                    'utils.h' => 'H utils.h',
                ]
            ]
        ]);
});

describe('__invoke -> actionZero', function () {
    it('should returns success and output list', function (array $paths, string $expected) {
        $this->input->shouldReceive('getOption')->with('tag')->andReturn(false)->once();
        $this->input->shouldReceive('getOption')->with('zero')->andReturn(true)->once();
        $this->input->shouldReceive('getOption')->with('stage')->andReturn(false)->once();
        $this->input->shouldReceive('getOption')->with('debug')->andReturn(false)->once();

        $entries = array_map(fn(string $path) => IndexEntry::new(
            FileStatFactory::new(),
            ObjectHashFactory::new(),
            TrackingFile::new($path),
        ), $paths);

        $index = GitIndex::new();
        foreach ($entries as $entry) {
            $index->addEntry($entry);
        }

        $this->indexRepository->shouldReceive('exists')->andReturn(true)->once();
        $this->indexRepository->shouldReceive('get')->andReturn($index)->once();
        $this->io->shouldReceive('echo')->with($expected)->once();

        $request = GitLsFilesRequest::new($this->input);
        $useCase = new GitLsFilesUseCase($this->io, $this->indexRepository);
        $actual = $useCase($request);

        expect($actual)->toBe(Result::Success);
    })
        ->with([
            [
                'paths' => [
                    'Makefile',
                    'handler.c',
                    'handler.h',
                    'html/errors/forbidden.html',
                    'html/errors/internal-server-error.html',
                    'html/errors/not-found.html',
                    'html/public/index.html',
                    'html/public/style.css',
                    'http/delete.http',
                    'http/get.http',
                    'http/patch.http',
                    'http/post.http',
                    'http/put.http',
                    'log/access.log',
                    'logger.c',
                    'main.c',
                    'utils.c',
                    'utils.h',
                ],
                'expected' => "Makefile\0handler.c\0handler.h\0html/errors/forbidden.html\0html/errors/internal-server-error.html\0html/errors/not-found.html\0html/public/index.html\0html/public/style.css\0http/delete.http\0http/get.http\0http/patch.http\0http/post.http\0http/put.http\0log/access.log\0logger.c\0main.c\0utils.c\0utils.h\0"
            ]
        ]);
});

describe('__invoke -> actionStage', function () {
    it('should returns success and output list', function (array $args, array $expected) {
        $this->input->shouldReceive('getOption')->with('tag')->andReturn(false)->once();
        $this->input->shouldReceive('getOption')->with('zero')->andReturn(false)->once();
        $this->input->shouldReceive('getOption')->with('stage')->andReturn(true)->once();
        $this->input->shouldReceive('getOption')->with('debug')->andReturn(false)->once();

        $entries = array_map(function (array $arg) {
            [$mode, $hash, $path] = $arg;

            return IndexEntry::new(
                FileStat::newForCacheinfo($mode),
                ObjectHash::parse($hash),
                TrackingFile::new($path),
            );
        }, $args);

        $index = GitIndex::new();
        foreach ($entries as $entry) {
            $index->addEntry($entry);
        }

        $this->indexRepository->shouldReceive('exists')->andReturn(true)->once();
        $this->indexRepository->shouldReceive('get')->andReturn($index)->once();
        $this->io->shouldReceive('writeln')
            ->withArgs(function (array $actual) use ($expected) {
                expect($actual)->toEqual($expected);

                return true;
            })
            ->once();

        $request = GitLsFilesRequest::new($this->input);
        $useCase = new GitLsFilesUseCase($this->io, $this->indexRepository);
        $actual = $useCase($request);

        expect($actual)->toBe(Result::Success);
    })
        ->with([
            [
                'args' => [
                    [33188, '836efb6e25a091dcb4ff8e1dbb2f0be6a5cbf14c', 'Makefile'],
                    [33188, '2140aa18a6652a24c60f27ce4f38b0a9464a7745', 'handler.c'],
                    [33188, '681a849ec5d6f84bc16f33420a092610a30c9311', 'handler.h'],
                    [33188, '6a83c8406b052537d9f4690ab4f2e4d4398dd204', 'html/errors/forbidden.html'],
                    [33261, '7a6e22cd7653ad95f5db8dbe954f19b72a92dd78', 'html/errors/internal-server-error.html'],
                    [33188, '7c3c127bfa3b15f35a74eba0e807b7424943ab65', 'html/errors/not-found.html'],
                    [33188, 'de54e7c8500c61c968b6514dc777f930836e22a4', 'html/public/index.html'],
                    [33188, '78a76a78f98534b718de8649e781f2be2803948a', 'html/public/style.css'],
                    [33261, '072049e63ec648b280a7c59fa573dd91df52dbda', 'http/delete.http'],
                    [33188, '5548ca2bbf1ddcf5fc0889e0b3a17c87054dd0ef', 'http/get.http'],
                    [33188, '386713ed07243f4039cdf07138811380446c03f4', 'http/patch.http'],
                    [33261, 'e8a9202e94ecae1ca71520a3e6ba8aa76322ac2b', 'http/post.http'],
                    [33261, '2b838fa9d8366ee8b1b65bdf4f2104a9c8cb57c7', 'http/put.http'],
                    [33188, '5ef12aa95f19e61567b5490a32379bb08db19f6e', 'log/access.log'],
                    [33188, '779f4632a86596c6b4ed35952d120c9a47bf8dc5', 'logger.c'],
                    [33188, '406e031b8824ea26ae0bf4d7579a1d89e3fb5906', 'main.c'],
                    [33261, '280b7c0551214951918583ec545ba514124cec77', 'utils.c'],
                    [33188, 'c55fcc4bb991ba6d44e4aacd3a33cb35575baa07', 'utils.h'],
                ],
                'expected' => [
                    'Makefile' => "100644 836efb6e25a091dcb4ff8e1dbb2f0be6a5cbf14c 0\tMakefile",
                    'handler.c' => "100644 2140aa18a6652a24c60f27ce4f38b0a9464a7745 0\thandler.c",
                    'handler.h' => "100644 681a849ec5d6f84bc16f33420a092610a30c9311 0\thandler.h",
                    'html/errors/forbidden.html' => "100644 6a83c8406b052537d9f4690ab4f2e4d4398dd204 0\thtml/errors/forbidden.html",
                    'html/errors/internal-server-error.html' => "100755 7a6e22cd7653ad95f5db8dbe954f19b72a92dd78 0\thtml/errors/internal-server-error.html",
                    'html/errors/not-found.html' => "100644 7c3c127bfa3b15f35a74eba0e807b7424943ab65 0\thtml/errors/not-found.html",
                    'html/public/index.html' => "100644 de54e7c8500c61c968b6514dc777f930836e22a4 0\thtml/public/index.html",
                    'html/public/style.css' => "100644 78a76a78f98534b718de8649e781f2be2803948a 0\thtml/public/style.css",
                    'http/delete.http' => "100755 072049e63ec648b280a7c59fa573dd91df52dbda 0\thttp/delete.http",
                    'http/get.http' => "100644 5548ca2bbf1ddcf5fc0889e0b3a17c87054dd0ef 0\thttp/get.http",
                    'http/patch.http' => "100644 386713ed07243f4039cdf07138811380446c03f4 0\thttp/patch.http",
                    'http/post.http' => "100755 e8a9202e94ecae1ca71520a3e6ba8aa76322ac2b 0\thttp/post.http",
                    'http/put.http' => "100755 2b838fa9d8366ee8b1b65bdf4f2104a9c8cb57c7 0\thttp/put.http",
                    'log/access.log' => "100644 5ef12aa95f19e61567b5490a32379bb08db19f6e 0\tlog/access.log",
                    'logger.c' => "100644 779f4632a86596c6b4ed35952d120c9a47bf8dc5 0\tlogger.c",
                    'main.c' => "100644 406e031b8824ea26ae0bf4d7579a1d89e3fb5906 0\tmain.c",
                    'utils.c' => "100755 280b7c0551214951918583ec545ba514124cec77 0\tutils.c",
                    'utils.h' => "100644 c55fcc4bb991ba6d44e4aacd3a33cb35575baa07 0\tutils.h",
                ]
            ]
        ]);
});

describe('__invoke -> actionDebug', function () {
    it('should returns success and output list', function (array $args, array $expectedValues) {
        $this->input->shouldReceive('getOption')->with('tag')->andReturn(false)->once();
        $this->input->shouldReceive('getOption')->with('zero')->andReturn(false)->once();
        $this->input->shouldReceive('getOption')->with('stage')->andReturn(false)->once();
        $this->input->shouldReceive('getOption')->with('debug')->andReturn(true)->once();

        $entries = array_map(function (array $arg) {
            [$path, $stat] = $arg;

            return IndexEntry::new(
                $stat,
                ObjectHashFactory::new(),
                TrackingFile::new($path),
            );
        }, $args);

        $index = GitIndex::new();
        foreach ($entries as $entry) {
            $index->addEntry($entry);
        }

        $this->indexRepository->shouldReceive('exists')->andReturn(true)->once();
        $this->indexRepository->shouldReceive('get')->andReturn($index)->once();

        foreach ($expectedValues as $expected) {
            $this->io->shouldReceive('writeln')
                ->withArgs(function (array $actual) use ($expected) {
                    expect($actual)->toEqual($expected);

                    return true;
                })
                ->once()
                ->ordered();
        }

        $request = GitLsFilesRequest::new($this->input);
        $useCase = new GitLsFilesUseCase($this->io, $this->indexRepository);
        $actual = $useCase($request);

        expect($actual)->toBe(Result::Success);
    })
        ->with([
            [
                'args' => [
                    [
                        'Makefile',
                        FileStat::new([
                            'dev' => 16777231,
                            'ino' => 63058701,
                            'mode' => 33188,
                            'nlink' => 1,
                            'uid' => 501,
                            'gid' => 21,
                            'rdev' => 1,
                            'size' => 51,
                            'atime' => 1744515841,
                            'mtime' => 1744515851,
                            'ctime' => 1744515861,
                            'blksize' => 4096,
                            'blocks' => 8,
                        ])
                    ],
                    [
                        'log/access.log',
                        FileStat::new([
                            'dev' => 16777232,
                            'ino' => 63058702,
                            'mode' => 33188,
                            'nlink' => 2,
                            'uid' => 502,
                            'gid' => 22,
                            'rdev' => 2,
                            'size' => 52,
                            'atime' => 1744515842,
                            'mtime' => 1744515852,
                            'ctime' => 1744515862,
                            'blksize' => 4096,
                            'blocks' => 8,
                        ])
                    ],
                    [
                        'main.c',
                        FileStat::new([
                            'dev' => 16777233,
                            'ino' => 63058703,
                            'mode' => 33188,
                            'nlink' => 3,
                            'uid' => 503,
                            'gid' => 23,
                            'rdev' => 3,
                            'size' => 53,
                            'atime' => 1744515843,
                            'mtime' => 1744515853,
                            'ctime' => 1744515863,
                            'blksize' => 4096,
                            'blocks' => 8,
                        ])
                    ],
                ],
                'expectedValues' => [
                    [
                        'Makefile',
                        '  ctime: 1744515861:0',
                        '  mtime: 1744515851:0',
                        "  dev: 16777231\tino: 63058701",
                        "  uid: 501\tgid: 21",
                        "  size: 51\tflags: 0",
                    ],
                    [
                        'log/access.log',
                        '  ctime: 1744515862:0',
                        '  mtime: 1744515852:0',
                        "  dev: 16777232\tino: 63058702",
                        "  uid: 502\tgid: 22",
                        "  size: 52\tflags: 0",
                    ],
                    [
                        'main.c',
                        '  ctime: 1744515863:0',
                        '  mtime: 1744515853:0',
                        "  dev: 16777233\tino: 63058703",
                        "  uid: 503\tgid: 23",
                        "  size: 53\tflags: 0",
                    ],
                ]
            ]
        ]);
});
