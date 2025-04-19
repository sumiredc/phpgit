<?php

declare(strict_types=1);

namespace Phpgit\Service;

use Phpgit\Domain\GitObject;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Domain\TrackingFile;
use RuntimeException;

final class FileToHashService
{
    public function __construct(
        private readonly FileRepositoryInterface $fileRepository
    ) {}

    /** 
     * @return array{0:TrackingFile,1:GitObject,2:ObjectHash}
     * @throws FileNotFoundException|RuntimeException 
     */
    public function __invoke(string $file)
    {
        $fileToObject = new FileToObjectService($this->fileRepository);
        [$trackingFile, $gitObject] = $fileToObject($file);

        return [$trackingFile, $gitObject, ObjectHash::new($gitObject->data)];
    }
}
