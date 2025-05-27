<?php

declare(strict_types=1);

namespace Phpgit\Service;

use Phpgit\Domain\GitFileMode;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\SegmentTree;
use Phpgit\Domain\TreeObject;
use UnexpectedValueException;

readonly final class SaveTreeObjectService
{
    public function __construct(
        private readonly ObjectRepositoryInterface $objectRepository,
    ) {}

    /**
     * @throws UnexpectedValueException
     */
    public function __invoke(SegmentTree $segmentTree): ObjectHash
    {
        return $this->saveTreeObject(
            TreeObject::new(),
            $segmentTree,
            F_GIT_OBJECTS_DIR
        );
    }

    /** 
     * @throws UnexpectedValueException
     */
    private function saveTreeObject(
        TreeObject $treeObject,
        SegmentTree $segmentTree,
        string $currentDir
    ): ObjectHash {
        foreach ($segmentTree->segments as $segment => $segmentValue) {
            $path = sprintf('%s/%s', $currentDir, $segment);

            /** 
             * @var GitFileMode $mode
             * @var ObjectHash $hash
             */
            [$mode, $hash] = match (true) {
                is_a($segmentValue, SegmentTree::class) => [
                    GitFileMode::Tree,
                    $this->saveTreeObject(TreeObject::new(), $segmentValue, $path) // Recursive
                ],

                is_a($segmentValue, IndexEntry::class) => [
                    $segmentValue->gitFileMode,
                    $segmentValue->objectHash
                ],

                // NOTE: This branch is not reached, because it manages by SegmentTree class.
                default => throw new UnexpectedValueException(
                    sprintf('unexpected segment value: %s', gettype($segmentValue))
                ), // @codeCoverageIgnore
            };

            $treeObject->appendEntry($mode, $hash, $segment);
        }

        return $this->objectRepository->save($treeObject);
    }
}
