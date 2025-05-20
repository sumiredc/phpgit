<?php

declare(strict_types=1);

namespace Phpgit\Infra\Repository;

use Phpgit\Domain\FileStat;
use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Domain\TrackedPath;
use RuntimeException;

readonly final class FileRepository implements FileRepositoryInterface
{
    public function exists(TrackedPath $trackedPath): bool
    {
        $filename = $trackedPath->full();

        return is_file($filename) && is_readable($filename);
    }

    public function existsDir(TrackedPath $trackedPath): bool
    {
        $dirname = $trackedPath->full();

        return is_dir($dirname) && is_readable($dirname);
    }

    public function existsDirByDirname(string $dir): bool
    {
        $dirname = sprintf('%s/%s', F_GIT_TRACKING_ROOT, $dir);

        return is_dir($dirname) && is_readable($dirname);
    }

    /** 
     * @throws RuntimeException 
     */
    public function getContents(TrackedPath $trackedPath): string
    {
        $content = file_get_contents($trackedPath->full());
        if ($content === false) {
            throw new RuntimeException(sprintf('failed to get contents: %s', $trackedPath->full()));
        }

        return $content;
    }

    /** 
     * @throws RuntimeException 
     */
    public function getStat(TrackedPath $trackedPath): FileStat
    {
        $stat = stat($trackedPath->full());
        if ($stat === false) {
            throw new RuntimeException(sprintf('failed to get stat: %s', $trackedPath->full()));
        }

        return FileStat::new($stat);
    }

    /** 
     * @return array<TrackedPath>
     */
    public function search(string $path): array
    {
        $trackedPath = TrackedPath::parse($path);
        if ($this->exists($trackedPath)) {
            return [$trackedPath];
        }

        if ($this->existsDir($trackedPath)) {
            return $this->searchDir($path);
        }

        return $this->searchByPattern($path);
    }

    private function searchDir(string $dir): array
    {
        if ($dir === '.') {
            $dir = '/';
        }

        /** @param array<TrackedPath> $targets */
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
                    $trackedPath = TrackedPath::parse($fullPath);
                    $targets[] = $trackedPath;
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
                $targets[] = TrackedPath::parse($fullPath);
            }
        }

        return $targets;
    }
}
