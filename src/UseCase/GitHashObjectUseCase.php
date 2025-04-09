<?php

namespace Phpgit\UseCase;

use Phpgit\Domain\GitPath;
use Phpgit\Domain\Result;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\StyleInterface;

final class GitHashObjectUseCase
{
    public function __construct(
        private readonly StyleInterface $io,
        private readonly LoggerInterface $logger
    ) {}

    public function __invoke(string $file): Result
    {
        $gitPath = new GitPath();

        $filePath = sprintf('%s/%s', $gitPath->trackingRoot, $file);
        if (!file_exists($filePath)) {
            $this->io->error(sprintf('File not found: %s', $file));
            return Result::Invalid;
        }

        $hash = $this->makeBlobObject($filePath, $gitPath);
        if ($hash === '') {
            return Result::Failure;
        }

        $this->io->success($hash);

        return Result::Success;
    }

    private function makeBlobObject(string $filePath, GitPath $gitPath): string
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            $this->logger->error(sprintf('failed to file_get_contents: %s', $filePath));
            return '';
        }

        $header = sprintf('blob %d\0', strlen($content));
        $object = sprintf('%s%s', $header, $content);
        $hash = sha1($object);
        $dir = substr($hash, 0, 2);
        $filename = substr($hash, 2);

        $objectDir = sprintf('%s/%s', $gitPath->objectsDir, $dir);
        if (!is_dir($objectDir)) {
            if (!mkdir($objectDir, 0755)) {
                $this->logger->error(sprintf('failed to mkdir: %s', $objectDir));
                return '';
            }
        }

        $objectPath = sprintf('%s/%s', $objectDir, $filename);
        file_put_contents($objectPath, gzcompress($object));

        return $hash;
    }
}
