<?php

declare(strict_types=1);

namespace Phpgit\Infra\Repository;

use Phpgit\Domain\BlobObject;
use Phpgit\Domain\CommitObject;
use Phpgit\Domain\CompressedPayload;
use Phpgit\Domain\GitObject;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\TreeObject;
use RuntimeException;
use UnexpectedValueException;

readonly final class ObjectRepository implements ObjectRepositoryInterface
{
    /** 
     * @throws RuntimeException 
     */
    public function save(GitObject $gitObject): ObjectHash
    {
        $objectHash = ObjectHash::new($gitObject->data);

        $objectDir = sprintf('%s/%s', F_GIT_OBJECTS_DIR, $objectHash->dir);
        if (!is_dir($objectDir) && !@mkdir($objectDir, 0755)) {
            throw new RuntimeException(sprintf('failed to mkdir: %s', $objectDir));
        }

        $compressed = CompressedPayload::fromOriginal($gitObject->data);

        if (file_put_contents($objectHash->fullPath(), $compressed->value) === false) {
            throw new RuntimeException(sprintf('failed to file_put_contents: %s', $objectHash->fullPath()));
        }

        return $objectHash;
    }

    /** 
     * @throws RuntimeException 
     */
    public function getCompressedPayload(ObjectHash $objectHash): CompressedPayload
    {
        $compressed = @file_get_contents($objectHash->fullPath());
        if ($compressed === false) {
            throw new RuntimeException(sprintf('failed to file_get_contents: %s', $objectHash->fullPath()));
        }

        return CompressedPayload::new($compressed);
    }

    /** 
     * @throws RuntimeException 
     */
    public function get(ObjectHash $objectHash): GitObject
    {
        $compressed = $this->getCompressedPayload($objectHash);

        return GitObject::parse($compressed->decompress());
    }

    /**
     * @throws RuntimeException
     * @throws UnexpectedValueException
     */
    public function getBlob(ObjectHash $objectHash): BlobObject
    {
        $object = $this->get($objectHash);
        if (is_a($object, BlobObject::class)) {
            return $object;
        }

        throw new UnexpectedValueException(
            sprintf('unexpected BlobObject: %s', $object->objectType->name)
        );
    }

    /**
     * @throws UnexpectedValueException
     * @throws RuntimeException 
     */
    public function getCommit(ObjectHash $objectHash): CommitObject
    {
        $object = $this->get($objectHash);
        if (is_a($object, CommitObject::class)) {
            return $object;
        }

        throw new UnexpectedValueException(
            sprintf('unexpected CommitObject: %s', $object->objectType->name)
        );
    }

    /**
     * @throws UnexpectedValueException
     * @throws RuntimeException 
     */
    public function getTree(ObjectHash $objectHash): TreeObject
    {
        $object = $this->get($objectHash);
        if (is_a($object, TreeObject::class)) {
            return $object;
        }

        throw new UnexpectedValueException(
            sprintf('unexpected TreeObject: %s', $object->objectType->name)
        );
    }

    public function exists(ObjectHash $objectHash): bool
    {
        return is_file($objectHash->fullPath());
    }
}
