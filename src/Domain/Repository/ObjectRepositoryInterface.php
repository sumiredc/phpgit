<?php

declare(strict_types=1);

namespace Phpgit\Domain\Repository;

use Phpgit\Domain\BlobObject;
use Phpgit\Domain\CommitObject;
use Phpgit\Domain\CompressedPayload;
use Phpgit\Domain\GitObject;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\TreeObject;
use RuntimeException;

interface ObjectRepositoryInterface
{
    /** 
     * @throws RuntimeException 
     */
    public function save(GitObject $gitObject): ObjectHash;

    /** 
     * @throws RuntimeException 
     */
    public function getCompressedPayload(ObjectHash $objectHash): CompressedPayload;

    /** 
     * @throws RuntimeException 
     */
    public function get(ObjectHash $objectHash): GitObject;

    /**
     * @throws RuntimeException
     */
    public function getBlob(ObjectHash $objectHash): BlobObject;

    /** 
     * @throws RuntimeException 
     */
    public function getCommit(ObjectHash $objectHash): CommitObject;

    /** 
     * @throws RuntimeException 
     */
    public function getTree(ObjectHash $objectHash): TreeObject;

    public function exists(ObjectHash $objectHash): bool;
}
