<?php

declare(strict_types=1);

use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Domain\Printer\PrinterInterface;
use Phpgit\Request\HashObjectRequest;
use Phpgit\UseCase\HashObjectUseCase;
use Symfony\Component\Console\Input\InputInterface;

beforeEach(function () {
    $this->input = Mockery::mock(InputInterface::class);
    $this->printer = Mockery::mock(PrinterInterface::class);
    $this->fileRepository = Mockery::mock(FileRepositoryInterface::class);
});

describe('__invoke', function () {
    it(
        'should returns to success',
        function (string $file, string $content, string $hash) {
            $this->input->shouldReceive('getArgument')->with('file')->andReturn($file);

            $this->fileRepository->shouldReceive('exists')->andReturn(true);
            $this->fileRepository->shouldReceive('getContents')->andReturn($content);
            $this->printer->shouldReceive('writeln')->with($hash)->once();

            $request = HashObjectRequest::new($this->input);
            $useCase = new HashObjectUseCase($this->printer, $this->fileRepository);
            $actual = $useCase($request);

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            ['README.md', '# README.md', 'c22da5372d73178c9a7317365c7fd127595df433'],
            ['src/main.rs', 'fn main() { println!("Hello world"); }', 'd0ecb8c7c2dc4904c15d1c5c31b3bebad8e97e33'],
        ]);

    it(
        'should returns to error, when throws FileNotFoundException',
        function (string $file) {
            $this->input->shouldReceive('getArgument')->with('file')->andReturn($file);

            $this->fileRepository->shouldReceive('exists')->andReturn(false);
            $this->printer->shouldReceive('writeln')
                ->with(
                    sprintf('fatal: could not open \'$s\' for reading: No such file or directory', $file)
                )
                ->once();

            $request = HashObjectRequest::new($this->input);
            $useCase = new HashObjectUseCase($this->printer, $this->fileRepository);
            $actual = $useCase($request);

            expect($actual)->toBe(Result::GitError);
        }
    )
        ->with([
            ['README.md'],
            ['src/main.rs']
        ]);

    it(
        'should returns to error, when throws Exception ignore FileNotFoundException',
        function (string $file, Throwable $expected) {
            $this->input->shouldReceive('getArgument')->with('file')->andReturn($file);

            $this->fileRepository->shouldReceive('exists')->andReturn(true)->once();
            $this->fileRepository->shouldReceive('getContents')->andThrow(new RuntimeException('failed to get contents: /full/path'))->once();
            $this->printer->shouldReceive('stackTrace')->withArgs(expectEqualArg($expected))->once();

            $request = HashObjectRequest::new($this->input);
            $useCase = new HashObjectUseCase($this->printer, $this->fileRepository);
            $actual = $useCase($request);

            expect($actual)->toBe(Result::InternalError);
        }
    )
        ->with([
            ['README.md', new RuntimeException('failed to get contents: /full/path')],
        ]);
});
