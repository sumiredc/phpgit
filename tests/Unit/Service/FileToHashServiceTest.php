<?php

declare(strict_types=1);

use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Service\FileToHashService;

beforeEach(function () {
    $this->fileRepository = Mockery::mock(FileRepositoryInterface::class);
});

describe('__invoke', function () {
    it('should convert file to hash', function (
        string $file,
        string $content,
        string $hash,
    ) {
        $this->fileRepository->shouldReceive('exists')->andReturn(true)->once();
        $this->fileRepository->shouldReceive('getContents')->andReturn($content)->once();

        $service = new FileToHashService($this->fileRepository);
        [$trackingFile, $blobObject, $objectHash] = $service($file);

        expect($trackingFile->path)->toBe($file);
        expect($blobObject->body)->toBe($content);
        expect($objectHash->value)->toBe($hash);
    })
        ->with([
            [
                'README.md',
                'file contents',
                sha1("blob 13\0" . 'file contents')
            ]
        ]);
});
