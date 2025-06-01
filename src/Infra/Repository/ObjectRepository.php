<?php

declare(strict_types=1);

namespace Phpgit\Infra\Repository;

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
        if (!is_dir($objectDir) && !@mkdir($objectDir, 0755)) {
            throw new RuntimeException(sprintf('failed to mkdir: %s', $objectDir));
        }

        $compressed = CompressedPayload::fromOriginal($gitObject->data);

        if (@file_put_contents($objectHash->fullPath(), $compressed->value) === false) {
            throw new RuntimeException(sprintf('failed to file_put_contents: %s', $objectHash->fullPath()));
        }

        return $objectHash;
    }

    /** @throws RuntimeException */
    public function getCompressedPayload(ObjectHash $objectHash): CompressedPayload
    {
        $compressed = @file_get_contents($objectHash->fullPath());
        if ($compressed === false) {
            throw new RuntimeException(sprintf('failed to file_get_contents: %s', $objectHash->fullPath()));
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
