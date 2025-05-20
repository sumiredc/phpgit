<?php

declare(strict_types=1);

namespace Phpgit\Infra\Repository;

use InvalidArgumentException;
use Phpgit\Domain\FileStat;
use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Domain\TrackingFile;
use RuntimeException;

readonly final class FileRepository implements FileRepositoryInterface
{
    public function exists(TrackingFile $trackingFile): bool
    {
        $filename = $trackingFile->fullPath();

        return is_file($filename) && is_readable($filename);
    }

    public function existsByFilename(string $file): bool
    {
        $filename = sprintf('%s/%s', F_GIT_TRACKING_ROOT, $file);

        return is_file($filename) && is_readable($filename);
    }

    public function existsDir(TrackingFile $trackingFile): bool
    {
        $dirname = $trackingFile->fullPath();

        return is_dir($dirname) && is_readable($dirname);
    }

    public function existsDirByDirname(string $dir): bool
    {
        $dirname = sprintf('%s/%s', F_GIT_TRACKING_ROOT, $dir);

        return is_dir($dirname) && is_readable($dirname);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function isOutSideRepository(string $path): bool
    {
        if (
            strpos($path, '/') === 0
            || strpos($path, '~') === 0
        ) {
            return true;
        }

        $segments = explode('/', $path);
        $resolvePath = function (array $carry, string $segment): array {
            if ($segment === '..') {
                if (empty($carry)) {
                    throw new InvalidArgumentException('Outside repository');
                }
                array_pop($carry);
            } else if (!in_array($segment, ['', '.'], true)) {
                $carry[] = $segment;
            }

            return $carry;
        };

        try {
            array_reduce($segments, $resolvePath, []);

            return false;
        } catch (InvalidArgumentException) {
            return true;
        }
    }

    /** 
     * @throws RuntimeException 
     */
    public function getContents(TrackingFile $trackingFile): string
    {
        $content = file_get_contents($trackingFile->fullPath());
        if ($content === false) {
            throw new RuntimeException(sprintf('failed to get contents: %s', $trackingFile->fullPath()));
        }

        return $content;
    }

    /** 
     * @throws RuntimeException 
     */
    public function getStat(TrackingFile $trackingFile): FileStat
    {
        $stat = stat($trackingFile->fullPath());
        if ($stat === false) {
            throw new RuntimeException(sprintf('failed to get stat: %s', $trackingFile->fullPath()));
        }

        return FileStat::new($stat);
    }

    /** 
     * @return array<TrackingFile>
     */
    public function search(string $path): array
    {
        $trackingFile = TrackingFile::new($path);
        if ($this->exists($trackingFile)) {
            return [$trackingFile];
        }

        if ($this->existsDir($trackingFile)) {
            return $this->searchDir($path);
        }

        return $this->searchByPattern($path);
    }

    private function searchDir(string $dir): array
    {
        if ($dir === '.') {
            $dir = '/';
        }

        /** @param array<TrackingFile> $targets */
        function searchFile(string $dir, array &$targets): void
        {
            // include ignore paths (ex: .ignore)
            $pattern = sprintf("%s%s", rtrim($dir, '/'), '/{.[!.],}*');
            $fullPaths = glob($pattern, GLOB_BRACE);

            if ($fullPaths === false) {
                throw new RuntimeException(sprintf(
                    'glob() failed: invalid pattern or internal error: %s',
                    $pattern
                ));
            }

            foreach ($fullPaths as $fullPath) {
                if (
                    !is_readable($fullPath)
                    || is_link($fullPath) // TODO: シンボリックリンクは一旦対象外とする
                ) {
                    continue;
                }

                if (is_file($fullPath)) {
                    $trackingFile = TrackingFile::fromFullPath($fullPath);
                    $targets[] = $trackingFile;
                }

                if (is_dir($fullPath)) {
                    if (basename($fullPath) === GIT_DIR) {
                        continue;
                    }

                    searchFile($fullPath, $targets);
                }
            }
        };

        $targets = [];
        searchFile(sprintf('%s/%s', F_GIT_TRACKING_ROOT, $dir), $targets);

        return $targets;
    }

    /**
     * @throws RuntimeException
     */
    private function searchByPattern(string $pattern): array
    {
        $fullPaths = glob($pattern);
        if ($fullPaths === false) {
            throw new RuntimeException(sprintf('failed to glob: %s', $pattern));
        }

        $targets = [];
        foreach ($fullPaths as $fullPath) {
            if (is_file($fullPath)) {
                $targets[] = TrackingFile::fromFullPath($fullPath);
            }
        }

        return $targets;
    }
}
