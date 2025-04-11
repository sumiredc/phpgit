<?php

namespace Phpgit\Service;

use Phpgit\Domain\GitObject;
use Phpgit\Domain\ObjectType;
use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Domain\TrackingFile;
use Phpgit\Exception\FileNotFoundException;
use RuntimeException;

final class FileToObjectService
{
    public function __construct(
        private readonly FileRepositoryInterface $fileRepository
    ) {}

    /** 
     * @return array{0:TrackingFile,1:GitObject}
     * @throws FileNotFoundException|RuntimeException
     */
    public function __invoke(string $file): array
    {
        $trackingFile = TrackingFile::parse($file);
        if (!$this->fileRepository->exists($trackingFile)) {
            throw new FileNotFoundException;
        }

        $content = $this->fileRepository->getContents($trackingFile);
        if (is_null($content)) {
            throw new RuntimeException('failed to get contents');
        }

        return [$trackingFile, GitObject::make(ObjectType::Blob, $content)];
    }
}
