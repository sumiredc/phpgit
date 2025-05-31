<?php

declare(strict_types=1);

namespace Phpgit\Service;

use Phpgit\Domain\GitIndex;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\SegmentTree;
use Phpgit\Exception\InvalidObjectException;
use RuntimeException;

readonly final class CreateSegmentTreeService implements CreateSegmentTreeServiceInterface
{
    public function __construct(
        private readonly ObjectRepositoryInterface $objectRepository
    ) {}

    /**
     * @throws InvalidObjectException
     * @throws RuntimeException
     */
    public function __invoke(GitIndex $gitIndex): SegmentTree
    {
        $segmentTree = SegmentTree::new();

        foreach ($gitIndex->entries as $entry) {
            if (!$this->objectRepository->exists($entry->objectHash)) {
                throw new InvalidObjectException(
                    sprintf(
                        'error: invalid object %s %s for \'%s\'',
                        $entry->gitFileMode->value,
                        $entry->objectHash->value,
                        $entry->trackedPath->value
                    )
                );
            }

            $segments = explode('/', $entry->trackedPath->value);
            $this->setSegument($segmentTree, $segments, 0, $entry);
        }

        return $segmentTree;
    }

    /**
     * @param array<string> $segments 
     * @throws RuntimeException
     */
    private function setSegument(
        SegmentTree &$currentTree,
        array $segments,
        int $currentIndex,
        IndexEntry $indexEntry
    ): void {
        $isFile = array_key_last($segments) === $currentIndex;
        $segment = $segments[$currentIndex];

        if ($isFile) {
            $currentTree->addEntry($segment, $indexEntry);
            return;
        }

        if (!$currentTree->isExists($segment)) {
            // NOTE: new next segment
            $currentTree->addNewSegmentTree($segment);
        }

        $nextTree = $currentTree->getSegmentTree($segment);

        $this->setSegument(
            $nextTree,
            $segments,
            $currentIndex + 1,
            $indexEntry
        );
    }
}
