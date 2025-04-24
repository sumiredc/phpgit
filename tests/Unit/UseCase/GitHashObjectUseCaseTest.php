<?php

declare(strict_types=1);

use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Lib\IOInterface;
use Phpgit\UseCase\GitHashObjectUseCase;

beforeEach(function () {
    $this->io = Mockery::mock(IOInterface::class);
    $this->fileRepository = Mockery::mock(FileRepositoryInterface::class);
});

describe('__invoke', function () {
    it('should returns to success', function (
        string $file,
        string $content,
        string $hash
    ) {
        $this->fileRepository->shouldReceive('exists')->andReturn(true);
        $this->fileRepository->shouldReceive('getContents')->andReturn($content);
        $this->io->shouldReceive('writeln')->with($hash)->once();

        $useCase = new GitHashObjectUseCase($this->io, $this->fileRepository);
        $actual = $useCase($file);

        expect($actual)->toBe(Result::Success);
    })
        ->with([
            ['README.md', '# README.md', 'c22da5372d73178c9a7317365c7fd127595df433'],
            ['src/main.rs', 'fn main() { println!("Hello world"); }', 'd0ecb8c7c2dc4904c15d1c5c31b3bebad8e97e33'],
        ]);

    it('should returns to error, when throws FileNotFoundException', function (string $file) {
        $this->fileRepository->shouldReceive('exists')->andReturn(false);
        $this->io->shouldReceive('writeln')
            ->with(
                sprintf('fatal: could not open \'$s\' for reading: No such file or directory', $file)
            )
            ->once();

        $useCase = new GitHashObjectUseCase($this->io, $this->fileRepository);
        $actual = $useCase($file);

        expect($actual)->toBe(Result::GitError);
    })
        ->with([
            ['README.md'],
            ['src/main.rs']
        ]);

    it('should returns to error, when throws Exception ignore FileNotFoundException', function (
        string $file,
        Throwable $expected
    ) {
        $this->fileRepository->shouldReceive('exists')->andReturn(true);
        $this->fileRepository->shouldReceive('getContents')->andReturnNull();
        $this->io->shouldReceive('stackTrace')
            ->with(Mockery::on(function (Throwable $actual) use ($expected) {
                $this->assertInstanceOf(RuntimeException::class, $actual);
                $this->assertEquals($expected, $actual);
                return true;
            }))
            ->once();

        $useCase = new GitHashObjectUseCase($this->io, $this->fileRepository);
        $actual = $useCase($file);

        expect($actual)->toBe(Result::GitError);
    })
        ->with([
            ['README.md', new RuntimeException('failed to get contents: README.md')],
        ]);
});
