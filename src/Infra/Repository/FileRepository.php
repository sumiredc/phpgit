<?php

declare(strict_types=1);

namespace Phpgit\Infra\Repository;

use Phpgit\Domain\FileStat;
use Phpgit\Domain\HashMap;
use Phpgit\Domain\PathType;
use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Domain\TrackedPath;
use RuntimeException;
use UnhandledMatchError;

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
     * @return HashMap<TrackedPath> key is relative path
     */
    public function search(TrackedPath $trackedPath, PathType $pathType): HashMap
    {
        return match ($pathType) {
            PathType::File => HashMap::parse([
                $trackedPath->value => $trackedPath
            ]),
            PathType::Directory => $this->searchDir($trackedPath),
            PathType::Pattern => $this->searchByPattern($trackedPath),
            PathType::Unknown => HashMap::new(),
            default => throw new UnhandledMatchError(
                sprintf('Unhandled enum case: %s', $pathType->name)
            ), // @codeCoverageIgnore
        };
    }

    /** 
     * @return HashMap<TrackedPath> key is relative path
     */
    private function searchDir(TrackedPath $trackedPath): HashMap
    {
        /** @param HashMap<TrackedPath> $targets - this is reference arg */
        function searchFile(TrackedPath $trackedPath, HashMap $targets): void
        {
            // include ignore paths (ex: .ignore)
            $pattern = sprintf("%s%s", rtrim($trackedPath->full(), '/'), '/{.[!.],}*');
            $fullPaths = glob($pattern, GLOB_BRACE);

            if ($fullPaths === false) {
                throw new RuntimeException(sprintf(
                    'glob() failed: invalid pattern or internal error: %s',
                    $pattern
                ));
            }

            /** @var array<string> $fullPaths */
            foreach ($fullPaths as $fullPath) {
                if (
                    !is_readable($fullPath)
                    || is_link($fullPath) // TODO: シンボリックリンクは一旦対象外とする
                ) {
                    continue;
                }

                if (is_file($fullPath)) {
                    $trackedPath = TrackedPath::parse($fullPath);
                    $targets->set($trackedPath->value, $trackedPath);
                }

                if (is_dir($fullPath)) {
                    if (basename($fullPath) === GIT_DIR) {
                        continue;
                    }

                    searchFile(TrackedPath::parse($fullPath), $targets);
                }
            }
        };

        $targets = HashMap::new();
        searchFile($trackedPath, $targets);

        return $targets;
    }

    /**
     * @return HashMap<TrackedPath> key is relative path
     * @throws RuntimeException
     */
    private function searchByPattern(TrackedPath $trackedPath): HashMap
    {
        $fullPaths = glob($trackedPath->value);
        if ($fullPaths === false) {
            throw new RuntimeException(sprintf('failed to glob: %s', $trackedPath->value));
        }

        $targets = HashMap::new();
        foreach ($fullPaths as $fullPath) {
            if (is_file($fullPath)) {
                $targets->set($trackedPath->value, TrackedPath::parse($fullPath));
            }
        }

        return $targets;
    }
}
