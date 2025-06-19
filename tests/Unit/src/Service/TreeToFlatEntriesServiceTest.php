<?php

declare(strict_types=1);

use Phpgit\Domain\GitFileMode;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\ObjectType;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\TreeEntry;
use Phpgit\Domain\TreeObject;
use Phpgit\Service\TreeToFlatEntriesService;

beforeEach(function () {
    $this->objectRepository = Mockery::mock(ObjectRepositoryInterface::class);
});

describe('__invoke', function () {
    it(
        '',
        function () {
            $rootTree = TreeObject::new();
            $rootEntries = [
                TreeEntry::new(ObjectType::Blob, GitFileMode::DefaultFile, 'blob-default1', ObjectHash::parse('fbaf7d194939ad56c622946d6f305f7de9bed0a8')),
                TreeEntry::new(ObjectType::Blob, GitFileMode::ExeFile, 'blob-exe1', ObjectHash::parse('faffac3dd1452d07ac3082145fa9591b382e160f')),
                TreeEntry::new(ObjectType::Tree, GitFileMode::Tree, 'tree1', ObjectHash::parse('081b3bbbc244693f20cf87f9de45db666faa4dc8')),
            ];
            foreach ($rootEntries as $entry) {
                $rootTree->appendEntry($entry->gitFileMode, $entry->objectHash, $entry->objectName);
            }

            $tree1 = TreeObject::new();
            $tree1Entries = [
                TreeEntry::new(ObjectType::Blob, GitFileMode::DefaultFile, 'blob-default2', ObjectHash::parse('3df627b458286383f51a526fbc0567905039c344')),
                TreeEntry::new(ObjectType::Blob, GitFileMode::ExeFile, 'blob-exe2', ObjectHash::parse('84afcb425aae830d7d4fe4cedc4f32f61e770fe7')),
                TreeEntry::new(ObjectType::Tree, GitFileMode::Tree, 'tree2', ObjectHash::parse('fc01489d8afd08431c7245b4216ea9d01856c3b9')),
            ];
            foreach ($tree1Entries as $entry) {
                $tree1->appendEntry($entry->gitFileMode, $entry->objectHash, $entry->objectName);
            }

            $tree2 = TreeObject::new();
            $tree2Entries = [
                TreeEntry::new(ObjectType::Blob, GitFileMode::DefaultFile, 'blob-default3', ObjectHash::parse('7320ed6befa5229fa0f31b81a1905def4acb3d9b')),
                TreeEntry::new(ObjectType::Blob, GitFileMode::ExeFile, 'blob-exe3', ObjectHash::parse('3313b1f784d1b761c1e2b1e335f746ea1e3be224')),
                TreeEntry::new(ObjectType::Tree, GitFileMode::Tree, 'tree3', ObjectHash::parse('f8933dba7b7326ee773408142b906c47fa336f9f')),
            ];
            foreach ($tree2Entries as $entry) {
                $tree2->appendEntry($entry->gitFileMode, $entry->objectHash, $entry->objectName);
            }

            $tree3 = TreeObject::new();
            $tree3Entries = [
                TreeEntry::new(ObjectType::Blob, GitFileMode::DefaultFile, 'blob-default4', ObjectHash::parse('442a016fe952275da6cf8cc18554781ec02e1e53')),
                TreeEntry::new(ObjectType::Blob, GitFileMode::ExeFile, 'blob-exe4', ObjectHash::parse('259d39d0b2e71f9e96472dc51ea9c97cbc06c67b')),
            ];
            foreach ($tree3Entries as $entry) {
                $tree3->appendEntry($entry->gitFileMode, $entry->objectHash, $entry->objectName);
            }

            $this->objectRepository->shouldReceive('getTree')->andReturn($tree1, $tree2, $tree3)->times(3);

            $expected = [
                ['blob-default1', 'fbaf7d194939ad56c622946d6f305f7de9bed0a8'],
                ['blob-exe1', 'faffac3dd1452d07ac3082145fa9591b382e160f'],
                ['tree1/blob-default2', '3df627b458286383f51a526fbc0567905039c344'],
                ['tree1/blob-exe2', '84afcb425aae830d7d4fe4cedc4f32f61e770fe7'],
                ['tree1/tree2/blob-default3', '7320ed6befa5229fa0f31b81a1905def4acb3d9b'],
                ['tree1/tree2/blob-exe3', '3313b1f784d1b761c1e2b1e335f746ea1e3be224'],
                ['tree1/tree2/tree3/blob-default4', '442a016fe952275da6cf8cc18554781ec02e1e53'],
                ['tree1/tree2/tree3/blob-exe4', '259d39d0b2e71f9e96472dc51ea9c97cbc06c67b'],
            ];

            $service = new TreeToFlatEntriesService($this->objectRepository);
            $actual = $service($rootTree);

            foreach ($expected as list($expectedKey, $expectedHash)) {
                expect($actual->get($expectedKey)->objectHash->value)->toBe($expectedHash);
            }

            expect(count($actual))->toBe(count($expected));
        }
    );
});
