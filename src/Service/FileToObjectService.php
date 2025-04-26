<?php

declare(strict_types=1);

namespace Phpgit\Service;

use Phpgit\Domain\BlobObject;
use Phpgit\Domain\GitObject;
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
        if (is_null($content)) {
            throw new RuntimeException(sprintf('failed to get contents: %s', $file));
        }

        return [$trackingFile, BlobObject::new($content)];
    }
}
