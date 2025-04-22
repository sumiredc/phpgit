<?php

declare(strict_types=1);

namespace Phpgit\UseCase;

use Phpgit\Domain\GitFileMode;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\ObjectType;
use Phpgit\Domain\Repository\IndexRepositoryInterface;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Domain\TreeObject;
use Phpgit\Exception\InvalidObjectException;
use Phpgit\Lib\IOInterface;
use RuntimeException;
use Throwable;
use UnexpectedValueException;

final class GitWriteTreeUseCase
{
    public function __construct(
        private readonly IOInterface $io,
        private readonly IndexRepositoryInterface $indexRepository,
        private readonly ObjectRepositoryInterface $objectRepository,
    ) {}

    public function __invoke(): Result
    {
        try {
            $objectHash = $this->createTree();
            $this->io->writeln($objectHash->value);

            return Result::Success;
        } catch (InvalidObjectException $ex) {
            $this->io->writeln([
                $ex->getMessage(),
                'fatal: git-write-tree: error building trees'
            ]);

            return Result::GitError;
        } catch (Throwable $th) {
            $this->io->stackTrace($th);

            return Result::GitError;
        }
    }

    private function createTree(): ObjectHash
    {
        /** 
         * key: segment name
         * @var array<string,IndexEntry|array<string,IndexEntry,array>> $segmentTree
         */
        $segmentTree = [];

        $gitIndex = $this->indexRepository->getOrCreate();

        foreach ($gitIndex->entries as $entry) {
            if (!$this->objectRepository->exists($entry->objectHash)) {
                throw new InvalidObjectException(
                    sprintf(
                        'error: invalid object %s %s for \'%s\'',
                        $entry->gitFileMode->value,
                        $entry->objectHash->value,
                        $entry->trackingFile->path
                    )
                );
            }

            $segments = explode('/', $entry->trackingFile->path);
            $this->setSegument($segmentTree, $segments, 0, $entry);
        }

        return $this->saveTreeObject(TreeObject::new(), $segmentTree, F_GIT_OBJECTS_DIR);
    }

    /**
     * @param array<string,IndexEntry|array<string,IndexEntry,array>> $currentTree 
     * @param array<string> $segments 
     */
    private function setSegument(array &$currentTree, array $segments, int $currentIndex, IndexEntry $indexEntry): void
    {
        $isFile = array_key_last($segments) === $currentIndex;
        $segment = $segments[$currentIndex];

        if ($isFile) {
            $currentTree[$segment] = $indexEntry;
            return;
        }

        if (!isset($currentTree[$segment])) {
            $currentTree[$segment] = [];
        }

        if (!is_array($currentTree[$segment])) {
            throw new RuntimeException(sprintf('next segment tree is not array: %s', gettype($currentTree[$segment])));
        }

        $this->setSegument($currentTree[$segment], $segments, $currentIndex + 1, $indexEntry);
    }

    /** 
     * @param array<string,IndexEntry|array<string,IndexEntry,array>> $segmentTree
     */
    private function saveTreeObject(TreeObject $treeObject, array $segmentTree, string $currentDir): ObjectHash
    {
        foreach ($segmentTree as $segment => $segmentValue) {
            $path = sprintf('%s/%s', $currentDir, $segment);

            /** 
             * @var GitFileMode $mode
             * @var ObjectType $type
             * @var ObjectHash $hash
             */
            [$mode, $type, $hash] = match (true) {
                is_array($segmentValue) => [
                    GitFileMode::Tree,
                    ObjectType::Tree,
                    $this->saveTreeObject(TreeObject::new(), $segmentValue, $path) // Recursive
                ],

                is_a($segmentValue, IndexEntry::class) => $this->getObjectMeta($segmentValue),

                default => throw new UnexpectedValueException(
                    sprintf('unexpected segment value: %s', gettype($segmentValue))
                ),
            };

            $treeObject->appendEntry($mode, $type, $hash, $segment);
        }

        return $this->objectRepository->save($treeObject);
    }

    /**
     * @return array{0:GitFileMode,1:ObjectType,2:ObjectHash}
     */
    private function getObjectMeta(IndexEntry $indexEntry): array
    {
        $object = $this->objectRepository->get($indexEntry->objectHash);

        return [$indexEntry->gitFileMode, $object->objectType, $indexEntry->objectHash];
    }
}
