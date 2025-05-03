<?php

declare(strict_types=1);

namespace Phpgit\Repository;

use Phpgit\Domain\CompressedPayload;
use Phpgit\Domain\GitObject;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use RuntimeException;

readonly final class ObjectRepository implements ObjectRepositoryInterface
{
    /** @throws RuntimeException */
    public function save(GitObject $gitObject): ObjectHash
    {
        $objectHash = ObjectHash::new($gitObject->data);

        $objectDir = sprintf('%s/%s', F_GIT_OBJECTS_DIR, $objectHash->dir);
        if (!is_dir($objectDir)) {
            if (!mkdir($objectDir, 0755)) {
                throw new RuntimeException('failed to mkdir');
            }
        }

        $objectPath = sprintf('%s/%s', $objectDir, $objectHash->filename);

        $compressed = CompressedPayload::fromOriginal($gitObject->data);

        if (file_put_contents($objectPath, $compressed->value) === false) {
            throw new RuntimeException('failed to file_put_contents');
        }

        return $objectHash;
    }

    /** @throws RuntimeException */
    public function getCompressedPayload(ObjectHash $objectHash): CompressedPayload
    {
        $path = $objectHash->fullPath();

        $compressed = file_get_contents($path);
        if ($compressed === false) {
            throw new RuntimeException('failed to file_get_contents', 500);
        }

        return CompressedPayload::new($compressed);
    }

    /** @throws RuntimeException */
    public function get(ObjectHash $objectHash): GitObject
    {
        $compressed = $this->getCompressedPayload($objectHash);

        return GitObject::parse($compressed->decompress());
    }

    public function exists(ObjectHash $objectHash): bool
    {
        return is_file($objectHash->fullPath());
    }
}
