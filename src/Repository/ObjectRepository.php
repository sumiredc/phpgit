<?php

namespace Phpgit\Repository;

use Phpgit\Domain\GitObject;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use RuntimeException;

final class ObjectRepository implements ObjectRepositoryInterface
{
    /** @throws RuntimeException */
    public function save(GitObject $gitObject): ObjectHash
    {
        $objectHash = ObjectHash::make($gitObject->data());

        $objectDir = sprintf('%s/%s', F_GIT_OBJECTS_DIR, $objectHash->dir);
        if (!is_dir($objectDir)) {
            if (!mkdir($objectDir, 0755)) {
                throw new RuntimeException('failed to mkdir');
            }
        }

        $objectPath = sprintf('%s/%s', $objectDir, $objectHash->filename);

        $compressed = $this->compress($gitObject->data());
        if (is_null($compressed)) {
            throw new RuntimeException('failed to compress');
        }

        if (file_put_contents($objectPath, $compressed) === false) {
            throw new RuntimeException('failed to file_put_contents');
        }

        return $objectHash;
    }

    public function getCompressed(ObjectHash $objectHash): string
    {
        $path = $objectHash->fullPath();

        $compressed = file_get_contents($path);
        if ($compressed === false) {
            throw new RuntimeException('failed to file_get_contents', 500);
        }

        return $compressed;
    }

    /** @throws RuntimeException */
    public function get(ObjectHash $objectHash): GitObject
    {
        $compressed = $this->getCompressed($objectHash);

        $uncompressed = $this->decompress($compressed);
        if (is_null($uncompressed)) {
            throw new RuntimeException('failed to decompress', 500);
        }

        $gitObject = GitObject::parse($uncompressed);
        if (is_null($gitObject)) {
            throw new RuntimeException('failed to parse for GitObject', 500);
        }

        return $gitObject;
    }

    public function exists(ObjectHash $objectHash): bool
    {
        return is_file($objectHash->fullPath());
    }

    private function compress(string $object): ?string
    {
        $compressed = gzcompress($object);
        if ($compressed === false) {
            return null;
        }

        return $compressed;
    }

    private function decompress(string $compressed): ?string
    {
        $uncompressed = zlib_decode($compressed);
        if ($uncompressed === false) {
            return null;
        }

        return $uncompressed;
    }
}
