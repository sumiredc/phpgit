<?php

declare(strict_types=1);

namespace Phpgit\Service;

use Phpgit\Domain\HashMap;
use Phpgit\Domain\ObjectType;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\TreeEntry;
use Phpgit\Domain\TreeObject;

readonly final class TreeToFlatEntriesService implements TreeToFlatEntriesServiceInterface
{
    public function __construct(
        private readonly ObjectRepositoryInterface $objectRepository
    ) {}

    /**
     * @return HashMap<TreeEntry> key: object name
     */
    public function __invoke(TreeObject $tree): HashMap
    {
        $entries = HashMap::new();
        $this->recursion($tree, [], $entries);

        return $entries;
    }

    /**
     * @param array<string> $segments
     * @param HashMap<TreeEntry> $entries
     */
    public function recursion(TreeObject $tree, array $segments, HashMap &$entries): void
    {

        foreach ($tree->entries() as $entry) {
            $currentPath = [...$segments, $entry->objectName];

            switch ($entry->objectType) {
                case ObjectType::Tree:
                    $nextTree = $this->objectRepository->getTree($entry->objectHash);

                    $this->recursion($nextTree, [...$segments, $entry->objectName], $entries);

                    break;

                default:
                    $entries->set(implode('/', $currentPath), $entry);
            }
        }
    }
}
