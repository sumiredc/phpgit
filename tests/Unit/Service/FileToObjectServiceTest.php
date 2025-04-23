<?php

declare(strict_types=1);

use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Exception\FileNotFoundException;
use Phpgit\Service\FileToHashService;
use Phpgit\Service\FileToObjectService;

beforeEach(function () {
    $this->fileRepository = Mockery::mock(FileRepositoryInterface::class);
});

describe('__invoke', function () {
    it('should convert file to object', function (
        string $file,
        string $content,
    ) {
        $this->fileRepository->shouldReceive('exists')->andReturn(true)->once();
        $this->fileRepository->shouldReceive('getContents')->andReturn($content)->once();

        $service = new FileToObjectService($this->fileRepository);
        [$trackingFile, $blobObject] = $service($file);

        expect($trackingFile->path)->toBe($file);
        expect($blobObject->body)->toBe($content);
    })
        ->with([
            [
                'README.md',
                'file contents',
            ]
        ]);

    it('should throws the FileNotFoundException, when don\'t exists file', function () {
        $this->fileRepository->shouldReceive('exists')->andReturn(false)->once();
        $this->fileRepository->shouldReceive('getContents')->never();

        $service = new FileToHashService($this->fileRepository);
        $service('file');
    })
        ->throws(FileNotFoundException::class);

    it('should throws the RuntimeException, when fails to get content', function () {
        $this->fileRepository->shouldReceive('exists')->andReturn(true)->once();
        $this->fileRepository->shouldReceive('getContents')->andReturnNull()->once();

        $service = new FileToHashService($this->fileRepository);
        $service('file');
    })
        ->throws(RuntimeException::class);
});
