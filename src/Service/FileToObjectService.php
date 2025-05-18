<?php

declare(strict_types=1);

namespace Phpgit\Service;

use Phpgit\Domain\BlobObject;
use Phpgit\Domain\GitObject;
use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Domain\TrackingFile;
use Phpgit\Exception\FileNotFoundException;
use RuntimeException;

/** @deprecated */
readonly final class FileToObjectService
{
    public function __construct(
        private readonly FileRepositoryInterface $fileRepository
    ) {}

    /** 
     * @return array{0:TrackingFile,1:GitObject}
     * @throws FileNotFoundException
     * @throws RuntimeException
     */
    public function __invoke(string $file): array
    {
        $trackingFile = TrackingFile::new($file);
        if (!$this->fileRepository->exists($trackingFile)) {
            throw new FileNotFoundException;
        }

        $content = $this->fileRepository->getContents($trackingFile);

        return [$trackingFile, BlobObject::new($content)];
    }
}
