<?php

declare(strict_types=1);

use Phpgit\Domain\BlobObject;
use Phpgit\Domain\FileStat;
use Phpgit\Domain\GitFileMode;
use Phpgit\Domain\HashMap;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\ObjectType;
use Phpgit\Domain\Repository\FileRepositoryInterface;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\TreeEntry;
use Phpgit\Helper\DiffIndexHelper;
use Tests\Factory\BlobObjectFactory;
use Tests\Factory\FileStatFactory;
use Tests\Factory\IndexEntryFactory;
use Tests\Factory\TrackedPathFactory;
use Tests\Factory\TreeEntryFactory;

beforeEach(function () {
    $this->fileRepository = Mockery::mock(FileRepositoryInterface::class);
    $this->objectRepository = Mockery::mock(ObjectRepositoryInterface::class);
});

describe('targetEntry', function () {
    beforeEach(function () {
        $this->indexEntries = [
            'a' => IndexEntryFactory::new(),
            'c' => IndexEntryFactory::new(),
            'd' => IndexEntryFactory::new(),
        ];

        $this->treeEntries = HashMap::new();
        $this->treeEntries->set('a', TreeEntryFactory::new());
        $this->treeEntries->set('b', TreeEntryFactory::new());
        $this->treeEntries->set('d', TreeEntryFactory::new());
        $this->treeEntries->set('e', TreeEntryFactory::new());
    });

    it(
        'returns null on both are null',
        function () {
            end($this->indexEntries);
            next($this->indexEntries);
            for ($i = 0; $i < count($this->treeEntries); $i++) {
                $this->treeEntries->next();
            }

            $helper = new DiffIndexHelper($this->fileRepository, $this->objectRepository);
            $actual = $helper->targetEntry($this->indexEntries, $this->treeEntries);

            expect($actual)->toBeNull();
        }
    );

    it(
        'returns d on tree is null',
        function () {
            end($this->indexEntries);
            for ($i = 0; $i < count($this->treeEntries); $i++) {
                $this->treeEntries->next();
            }

            $helper = new DiffIndexHelper($this->fileRepository, $this->objectRepository);
            $actual = $helper->targetEntry($this->indexEntries, $this->treeEntries);

            expect($actual)->toBe('d');
        }
    );

    it(
        'returns e on index is null',
        function () {
            end($this->indexEntries);
            next($this->indexEntries);
            for ($i = 0; $i < count($this->treeEntries) - 1; $i++) {
                $this->treeEntries->next();
            }

            $helper = new DiffIndexHelper($this->fileRepository, $this->objectRepository);
            $actual = $helper->targetEntry($this->indexEntries, $this->treeEntries);

            expect($actual)->toBe('e');
        }
    );

    it(
        'returns c on sort result b, c',
        function () {
            next($this->indexEntries);
            $this->treeEntries->next();

            $helper = new DiffIndexHelper($this->fileRepository, $this->objectRepository);
            $actual = $helper->targetEntry($this->indexEntries, $this->treeEntries);

            expect($actual)->toBe('b');
        }
    );
});

describe('nextTargetEntry', function () {
    beforeEach(function () {
        $this->indexEntries = [
            'a' => IndexEntryFactory::new(),
            'c' => IndexEntryFactory::new(),
            'd' => IndexEntryFactory::new(),
        ];

        $this->treeEntries = HashMap::new();
        $this->treeEntries->set('a', TreeEntryFactory::new());
        $this->treeEntries->set('b', TreeEntryFactory::new());
        $this->treeEntries->set('d', TreeEntryFactory::new());
        $this->treeEntries->set('e', TreeEntryFactory::new());
    });

    it(
        'returns next index on old and new are not null',
        function () {
            $helper = new DiffIndexHelper($this->fileRepository, $this->objectRepository);
            $actual = $helper->nextTargetEntry(
                TreeEntryFactory::new(),
                IndexEntryFactory::new(),
                $this->treeEntries,
                $this->indexEntries,
            );

            expect($actual)->toBe('b');
        }
    );

    it(
        'returns b on sort result b, c',
        function () {
            $helper = new DiffIndexHelper($this->fileRepository, $this->objectRepository);
            $actual = $helper->nextTargetEntry(
                TreeEntryFactory::new(),
                IndexEntryFactory::new(),
                $this->treeEntries,
                $this->indexEntries,
            );

            expect($actual)->toBe('b');
        }
    );

    it(
        'returns null on entries key is last pointer',
        function () {
            end($this->indexEntries);
            for ($i = 0; $i < count($this->treeEntries) - 1; $i++) {
                $this->treeEntries->next();
            }

            $helper = new DiffIndexHelper($this->fileRepository, $this->objectRepository);
            $actual = $helper->nextTargetEntry(
                TreeEntryFactory::new(),
                IndexEntryFactory::new(),
                $this->treeEntries,
                $this->indexEntries,
            );

            expect($actual)->toBeNull();
        }
    );
});

describe('getOldStatusFromTree', function () {
    it(
        'returns filemode and hash',
        function (
            ?TreeEntry $entry,
            GitFileMode $expectedFileMode,
            string $expectedHash
        ) {
            $helper = new DiffIndexHelper($this->fileRepository, $this->objectRepository);
            [$actualFileMode, $actualHash] = $helper->getOldStatusFromTree($entry);

            expect($actualFileMode)->toBe($expectedFileMode);
            expect($actualHash->value)->toBe($expectedHash);
        }
    )
        ->with([
            [null, GitFileMode::Unknown, '0000000000000000000000000000000000000000'],
            fn() => [
                TreeEntry::new(ObjectType::Blob, GitFileMode::DefaultFile, 'default-blob', ObjectHash::parse('12e7095a06fe7eed8df2d893db224786f6805add')),
                GitFileMode::DefaultFile,
                '12e7095a06fe7eed8df2d893db224786f6805add',
            ],
            fn() => [
                TreeEntry::new(ObjectType::Blob, GitFileMode::ExeFile, 'exec-blob', ObjectHash::parse('3b6fdb81fa6e04621f10694a44ba4e1ea272f234')),
                GitFileMode::ExeFile,
                '3b6fdb81fa6e04621f10694a44ba4e1ea272f234',
            ],
            fn() => [
                TreeEntry::new(ObjectType::Tree, GitFileMode::Tree, 'tree', ObjectHash::parse('80655da8d80aaaf92ce5357e7828dc09adb00993')),
                GitFileMode::Tree,
                '80655da8d80aaaf92ce5357e7828dc09adb00993',
            ],
        ]);
});

describe('getNewStatusFromIndex', function () {
    it(
        'returns filemode and hash',
        function (
            ?IndexEntry $entry,
            GitFileMode $expectedFileMode,
            string $expectedHash
        ) {
            $helper = new DiffIndexHelper($this->fileRepository, $this->objectRepository);
            [$actualFileMode, $actualHash] = $helper->getNewStatusFromIndex($entry);

            expect($actualFileMode)->toBe($expectedFileMode);
            expect($actualHash->value)->toBe($expectedHash);
        }
    )
        ->with([
            [null, GitFileMode::Unknown, '0000000000000000000000000000000000000000'],
            fn() => [
                IndexEntry::new(
                    FileStatFactory::default(),
                    ObjectHash::parse('829c3804401b0727f70f73d4415e162400cbe57b'),
                    TrackedPathFactory::new()
                ),
                GitFileMode::DefaultFile,
                '829c3804401b0727f70f73d4415e162400cbe57b'
            ],
            fn() => [
                IndexEntry::new(
                    FileStatFactory::exec(),
                    ObjectHash::parse('8151325dcdbae9e0ff95f9f9658432dbedfdb209'),
                    TrackedPathFactory::new()
                ),
                GitFileMode::ExeFile,
                '8151325dcdbae9e0ff95f9f9658432dbedfdb209'
            ]
        ]);
});

describe('getNewStatusFromWorktree', function () {
    it(
        'returns Unknown and zero on given null when remove form index case',
        function () {
            $this->fileRepository->shouldReceive('exists')->never();

            $helper = new DiffIndexHelper($this->fileRepository, $this->objectRepository);
            [$actualFileMode, $actualHash] = $helper->getNewStatusFromWorktree(null);

            expect($actualFileMode)->toBe(GitFileMode::Unknown);
            expect($actualHash->value)->toBe('0000000000000000000000000000000000000000');
        }
    );

    it(
        'returns Unknown and zero on given to does not exists file when delete file case',
        function () {
            $entry = IndexEntryFactory::new();

            $this->fileRepository->shouldReceive('exists')->withArgs(expectEqualArg($entry->trackedPath))->andReturn(false)->once();

            $helper = new DiffIndexHelper($this->fileRepository, $this->objectRepository);
            [$actualFileMode, $actualHash] = $helper->getNewStatusFromWorktree($entry);

            expect($actualFileMode)->toBe(GitFileMode::Unknown);
            expect($actualHash->value)->toBe('0000000000000000000000000000000000000000');
        }
    );

    it(
        'returns file mode and zero when modified file case',
        function (
            FileStat $stat,
            FileStat $newStat,
            string $contents,
            string $newContents,
            GitFileMode $expectedFileMode,
            string $expectedHash
        ) {
            $trackedPath = TrackedPathFactory::new();
            $entry = IndexEntry::new(
                $stat,
                ObjectHash::new(BlobObject::new($contents)->data),
                $trackedPath
            );

            $this->fileRepository
                ->shouldReceive('exists')->withArgs(expectEqualArg($trackedPath))->andReturn(true)->once()
                ->shouldReceive('getContents')->withArgs(expectEqualArg($trackedPath))->andReturn($newContents)->once()
                ->shouldReceive('getStat')->withArgs(expectEqualArg($trackedPath))->andReturn($newStat);

            $helper = new DiffIndexHelper($this->fileRepository, $this->objectRepository);
            [$actualFileMode, $actualHash] = $helper->getNewStatusFromWorktree($entry);

            expect($actualFileMode)->toBe($expectedFileMode);
            expect($actualHash->value)->toBe($expectedHash);
        }
    )
        ->with([
            'change mode' => [
                FileStatFactory::default(),
                FileStatFactory::exec(),
                'dummy-contents',
                'dummy-contents',
                GitFileMode::ExeFile,
                '0000000000000000000000000000000000000000'
            ],
            'change contents' => [
                FileStatFactory::default(),
                FileStatFactory::default(),
                'before-contents',
                'after-contents',
                GitFileMode::DefaultFile,
                '0000000000000000000000000000000000000000'
            ],
        ]);

    it(
        'returns file mode and hash when same file case',
        function (
            FileStat $stat,
            FileStat $newStat,
            string $contents,
            string $newContents,
            GitFileMode $expectedFileMode,
            string $expectedHash
        ) {
            $trackedPath = TrackedPathFactory::new();
            $entry = IndexEntry::new(
                $stat,
                ObjectHash::new(BlobObject::new($contents)->data),
                $trackedPath
            );

            $this->fileRepository
                ->shouldReceive('exists')->withArgs(expectEqualArg($trackedPath))->andReturn(true)->once()
                ->shouldReceive('getContents')->withArgs(expectEqualArg($trackedPath))->andReturn($newContents)->once()
                ->shouldReceive('getStat')->withArgs(expectEqualArg($trackedPath))->andReturn($newStat);

            $helper = new DiffIndexHelper($this->fileRepository, $this->objectRepository);
            [$actualFileMode, $actualHash] = $helper->getNewStatusFromWorktree($entry);

            expect($actualFileMode)->toBe($expectedFileMode);
            expect($actualHash->value)->toBe($expectedHash);
        }
    )
        ->with([
            [
                FileStatFactory::default(),
                FileStatFactory::default(),
                'dummy-contents',
                'dummy-contents',
                GitFileMode::DefaultFile,
                '1712560a49b85605ae1a3c4a9a62e7ab5430f749'
            ]
        ]);
});

describe('getOldContentsFromTree', function () {
    it(
        'returns null on given null',
        function () {
            $this->objectRepository->shouldReceive('exists')->never();

            $helper = new DiffIndexHelper($this->fileRepository, $this->objectRepository);
            $actual = $helper->getOldContentsFromTree(null);

            expect($actual)->toBeNull();
        }
    );

    it(
        'returns blob body on exists file',
        function () {
            $expected = BlobObjectFactory::new();

            $entry = TreeEntryFactory::new();
            $this->objectRepository
                ->shouldReceive('getBlob')->withArgs(expectEqualArg($entry->objectHash))->andReturn($expected)->once();

            $helper = new DiffIndexHelper($this->fileRepository, $this->objectRepository);
            $actual = $helper->getOldContentsFromTree($entry);

            expect($actual)->toBe($expected->body);
        }
    );
});

describe('getNewContentsFromIndex', function () {
    it(
        'returns null on given null',
        function () {
            $this->objectRepository->shouldReceive('exists')->never();

            $helper = new DiffIndexHelper($this->fileRepository, $this->objectRepository);
            $actual = $helper->getNewContentsFromIndex(null);

            expect($actual)->toBeNull();
        }
    );

    it(
        'returns null on does not exists file',
        function () {
            $entry = IndexEntryFactory::new();
            $this->objectRepository->shouldReceive('exists')->withArgs(expectEqualArg($entry->objectHash))->andReturn(false)->once();

            $helper = new DiffIndexHelper($this->fileRepository, $this->objectRepository);
            $actual = $helper->getNewContentsFromIndex($entry);

            expect($actual)->toBeNull();
        }
    );

    it(
        'returns blob body on exists file',
        function () {
            $expected = BlobObjectFactory::new();

            $entry = IndexEntryFactory::new();
            $this->objectRepository
                ->shouldReceive('exists')->withArgs(expectEqualArg($entry->objectHash))->andReturn(true)->once()
                ->shouldReceive('getBlob')->withArgs(expectEqualArg($entry->objectHash))->andReturn($expected)->once();

            $helper = new DiffIndexHelper($this->fileRepository, $this->objectRepository);
            $actual = $helper->getNewContentsFromIndex($entry);

            expect($actual)->toBe($expected->body);
        }
    );
});

describe('getNewContentsFromWorktree', function () {
    it(
        'returns null on given null',
        function () {
            $this->fileRepository->shouldReceive('exists')->never();

            $helper = new DiffIndexHelper($this->fileRepository, $this->objectRepository);
            $actual = $helper->getNewContentsFromWorktree(null);

            expect($actual)->toBeNull();
        }
    );

    it(
        'returns null on does not exists file',
        function () {
            $entry = IndexEntryFactory::new();
            $this->fileRepository->shouldReceive('exists')->withArgs(expectEqualArg($entry->trackedPath))->andReturn(false)->once();

            $helper = new DiffIndexHelper($this->fileRepository, $this->objectRepository);
            $actual = $helper->getNewContentsFromWorktree($entry);

            expect($actual)->toBeNull();
        }
    );

    it(
        'returns contents on exists file',
        function () {
            $expected = 'dummy-contents';

            $entry = IndexEntryFactory::new();
            $this->fileRepository
                ->shouldReceive('exists')->withArgs(expectEqualArg($entry->trackedPath))->andReturn(true)->once()
                ->shouldReceive('getContents')->withArgs(expectEqualArg($entry->trackedPath))->andReturn($expected)->once();

            $helper = new DiffIndexHelper($this->fileRepository, $this->objectRepository);
            $actual = $helper->getNewContentsFromWorktree($entry);

            expect($actual)->toBe($expected);
        }
    );
});

describe('countDiff', function () {
    it(
        'returns not diff state on old and new are null',
        function () {
            $helper = new DiffIndexHelper($this->fileRepository, $this->objectRepository);
            $actual = $helper->countDiff(null, null, 'dummy-path');

            expect($actual->path)->toBe('dummy-path');
            expect($actual->total)->toBe(0);
            expect($actual->isChanged())->toBeFalse();
        }
    );

    it(
        'returns droped diff state on new is null',
        function () {
            $helper = new DiffIndexHelper($this->fileRepository, $this->objectRepository);
            $actual = $helper->countDiff(
                'old contents',
                null,
                'dummy-path'
            );

            expect($actual->path)->toBe('dummy-path');
            expect($actual->total)->toBe(0);
            expect($actual->isDropedFile())->toBeTrue();
        }
    );

    it(
        'returns added diff state on old is null',
        function () {
            $helper = new DiffIndexHelper($this->fileRepository, $this->objectRepository);
            $actual = $helper->countDiff(
                null,
                '',
                'dummy-path'
            );

            expect($actual->path)->toBe('dummy-path');
            expect($actual->total)->toBe(0);
            expect($actual->isAddedFile())->toBeTrue();
        }
    );

    it(
        'returns diff on does not drop and add file',
        function (
            string $old,
            string $new,
            int $expectedInsertions,
            int $expectedDeletions
        ) {
            $helper = new DiffIndexHelper($this->fileRepository, $this->objectRepository);
            $actual = $helper->countDiff(
                $old,
                $new,
                'dummy-path'
            );

            expect($actual->path)->toBe('dummy-path');
            expect($actual->isAddedFile())->toBeFalse();
            expect($actual->isDropedFile())->toBeFalse();

            expect($actual->insertions)->toBe($expectedInsertions);
            expect($actual->deletions)->toBe($expectedDeletions);
        }
    )
        ->with([
            ['', '', 0, 0],
            ["first\nsecond\n", "first\nsecond\nthird\n", 1, 0],
            ["first\nsecond\nthird\n", "first\nsecond\n", 0, 1],
            ["first\nsecond\nthird\nforth\nfifth\n", "first\nsecond\nforth\nfifth\nsixth\n", 1, 1]
        ]);
});

describe('isSame', function () {
    it(
        'returns result whether it was same or not',
        function (
            GitFileMode $oldMode,
            GitFileMode $newMode,
            ObjectHash $oldHash,
            ObjectHash $newHash,
            bool $expected
        ) {
            $helper = new DiffIndexHelper($this->fileRepository, $this->objectRepository);
            $actual = $helper->isSame($newMode, $oldMode, $newHash, $oldHash);

            expect($actual)->toBe($expected);
        }
    )
        ->with([
            [
                GitFileMode::DefaultFile,
                GitFileMode::ExeFile,
                ObjectHash::parse('cdda83364300812cce723eee71711406f5069fce'),
                ObjectHash::parse('cdda83364300812cce723eee71711406f5069fce'),
                false
            ],
            [
                GitFileMode::DefaultFile,
                GitFileMode::DefaultFile,
                ObjectHash::parse('cdda83364300812cce723eee71711406f5069fce'),
                ObjectHash::parse('c98592c3788aa048fed509608c1d9594563b6f68'),
                false
            ],
            [
                GitFileMode::DefaultFile,
                GitFileMode::DefaultFile,
                ObjectHash::parse('c98592c3788aa048fed509608c1d9594563b6f68'),
                ObjectHash::parse('c98592c3788aa048fed509608c1d9594563b6f68'),
                true
            ],
        ]);
});

describe('isAdded', function () {
    it(
        'returns result whether it was added or not',
        function (
            GitFileMode $oldMode,
            ObjectHash $oldHash,
            bool $expected
        ) {
            $helper = new DiffIndexHelper($this->fileRepository, $this->objectRepository);
            $actual = $helper->isAdded($oldMode, $oldHash);

            expect($actual)->toBe($expected);
        }
    )
        ->with([
            [GitFileMode::Unknown, ObjectHash::zero(), true],
            [GitFileMode::Unknown, ObjectHash::parse('cdda83364300812cce723eee71711406f5069fce'), false],
            [GitFileMode::DefaultFile, ObjectHash::zero(), false],
            [GitFileMode::ExeFile, ObjectHash::zero(), false],
            [GitFileMode::Tree, ObjectHash::zero(), false],
            [GitFileMode::SubModule, ObjectHash::zero(), false],
            [GitFileMode::SymbolicLink, ObjectHash::zero(), false],
        ]);
});

describe('isModefied', function () {
    it(
        'returns result whether it was modefied or not',
        function (
            GitFileMode $oldMode,
            ObjectHash $oldHash,
            GitFileMode $newMode,
            ObjectHash $newHash,
            bool $expected
        ) {
            $helper = new DiffIndexHelper($this->fileRepository, $this->objectRepository);
            $actual = $helper->isModefied($oldMode, $oldHash, $newMode, $newHash);

            expect($actual)->toBe($expected);
        }
    )
        ->with([
            [
                GitFileMode::Unknown,
                ObjectHash::zero(),
                GitFileMode::DefaultFile,
                ObjectHash::parse('cdda83364300812cce723eee71711406f5069fce'),
                false
            ],
            [
                GitFileMode::DefaultFile,
                ObjectHash::parse('cdda83364300812cce723eee71711406f5069fce'),
                GitFileMode::Unknown,
                ObjectHash::parse('cdda83364300812cce723eee71711406f5069fce'),
                false
            ],
            [
                GitFileMode::DefaultFile,
                ObjectHash::parse('cdda83364300812cce723eee71711406f5069fce'),
                GitFileMode::DefaultFile,
                ObjectHash::parse('cdda83364300812cce723eee71711406f5069fce'),
                false
            ],
            [
                GitFileMode::DefaultFile,
                ObjectHash::parse('cdda83364300812cce723eee71711406f5069fce'),
                GitFileMode::ExeFile,
                ObjectHash::parse('cdda83364300812cce723eee71711406f5069fce'),
                true
            ],
            [
                GitFileMode::DefaultFile,
                ObjectHash::parse('cdda83364300812cce723eee71711406f5069fce'),
                GitFileMode::DefaultFile,
                ObjectHash::parse('c98592c3788aa048fed509608c1d9594563b6f68'),
                true
            ],
        ]);
});

describe('isDeleted', function () {
    it(
        'returns result whether it was deleted or not',
        function (
            GitFileMode $newMode,
            ObjectHash $newHash,
            bool $expected
        ) {
            $helper = new DiffIndexHelper($this->fileRepository, $this->objectRepository);
            $actual = $helper->isDeleted($newMode, $newHash);

            expect($actual)->toBe($expected);
        }
    )
        ->with([
            [GitFileMode::Unknown, ObjectHash::zero(), true],
            [GitFileMode::Unknown, ObjectHash::parse('cdda83364300812cce723eee71711406f5069fce'), false],
            [GitFileMode::DefaultFile, ObjectHash::zero(), false],
            [GitFileMode::ExeFile, ObjectHash::zero(), false],
            [GitFileMode::Tree, ObjectHash::zero(), false],
            [GitFileMode::SubModule, ObjectHash::zero(), false],
            [GitFileMode::SymbolicLink, ObjectHash::zero(), false],
        ]);
});
