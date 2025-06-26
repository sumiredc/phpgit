<?php

declare(strict_types=1);

use Phpgit\Command\CommandInterface;
use Phpgit\Domain\CommitObject;
use Phpgit\Domain\DiffStat;
use Phpgit\Domain\GitFileMode;
use Phpgit\Domain\GitIndex;
use Phpgit\Domain\HashMap;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\ObjectType;
use Phpgit\Domain\Printer\PrinterInterface;
use Phpgit\Domain\Repository\IndexRepositoryInterface;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Domain\TrackedPath;
use Phpgit\Domain\TreeEntry;
use Phpgit\Helper\DiffIndexHelperInterface;
use Phpgit\Request\DiffIndexRequest;
use Phpgit\Service\ResolveRevisionServiceInterface;
use Phpgit\Service\TreeToFlatEntriesServiceInterface;
use Phpgit\UseCase\DiffIndexUseCase;
use Symfony\Component\Console\Input\InputInterface;
use Tests\Factory\FileStatFactory;
use Tests\Factory\GitSignatureFactory;
use Tests\Factory\ObjectHashFactory;
use Tests\Factory\TreeObjectFactory;

beforeAll(function () {
    $command = Mockery::mock(CommandInterface::class);
    $command->shouldReceive('addOption')->shouldReceive('addArgument');
    DiffIndexRequest::setUp($command);
});

beforeEach(function () {
    $this->printer = Mockery::mock(PrinterInterface::class);
    $this->indexRepository = Mockery::mock(IndexRepositoryInterface::class);
    $this->objectRepository = Mockery::mock(ObjectRepositoryInterface::class);
    $this->resolveRevisionService = Mockery::mock(ResolveRevisionServiceInterface::class);
    $this->treeToFlatEntriesService = Mockery::mock(TreeToFlatEntriesServiceInterface::class);
    $this->diffIndexHelper = Mockery::mock(DiffIndexHelperInterface::class);

    $this->input = Mockery::mock(InputInterface::class);
});

describe('__invoke', function () {
    it(
        'throws an exception on does not resolve hash',
        function () {
            $treeIsh = 'dont-resolve-tree';
            $expected = 'fatal: ambiguous argument \'dont-resolve-tree\': unknown revision or path not in the working tree.';

            $this->input
                ->shouldReceive('getArgument')->with('tree-ish')->andReturn($treeIsh)
                ->shouldReceive('getOption')->with('cached')->andReturn(false)
                ->shouldReceive('getOption')->with('stat')->andReturn(false);

            $this->resolveRevisionService->shouldReceive('__invoke')->with($treeIsh)->andReturnNull()->once();
            $this->printer->shouldReceive('writeln')->with($expected)->once();

            $request = DiffIndexRequest::new($this->input);
            $useCase = new DiffIndexUseCase(
                $this->printer,
                $this->indexRepository,
                $this->objectRepository,
                $this->resolveRevisionService,
                $this->treeToFlatEntriesService,
                $this->diffIndexHelper
            );

            $actual = $useCase($request);

            expect($actual)->toBe(Result::GitError);
        }
    );

    it(
        'throws an exception on does not exist tree',
        function () {
            $treeIsh = 'dont-exists-tree';
            $objectHash = ObjectHash::parse('829c3804401b0727f70f73d4415e162400cbe57b');
            $expected = 'fatal: bad object 829c3804401b0727f70f73d4415e162400cbe57b';

            $this->input
                ->shouldReceive('getArgument')->with('tree-ish')->andReturn($treeIsh)
                ->shouldReceive('getOption')->with('cached')->andReturn(false)
                ->shouldReceive('getOption')->with('stat')->andReturn(false);

            $this->resolveRevisionService->shouldReceive('__invoke')->with($treeIsh)->andReturn($objectHash)->once();
            $this->objectRepository->shouldReceive('exists')->withArgs(expectEqualArg($objectHash))->andReturn(false)->once();
            $this->printer->shouldReceive('writeln')->with($expected)->once();

            $request = DiffIndexRequest::new($this->input);
            $useCase = new DiffIndexUseCase(
                $this->printer,
                $this->indexRepository,
                $this->objectRepository,
                $this->resolveRevisionService,
                $this->treeToFlatEntriesService,
                $this->diffIndexHelper
            );

            $actual = $useCase($request);

            expect($actual)->toBe(Result::GitError);
        }
    );

    it(
        'returns internal error and output stack trace on throws unexpected error',
        function () {
            $treeIsh = 'dummy-tree';
            $expected = new RuntimeException('internal server error');

            $this->input
                ->shouldReceive('getArgument')->with('tree-ish')->andReturn($treeIsh)
                ->shouldReceive('getOption')->with('cached')->andReturn(false)
                ->shouldReceive('getOption')->with('stat')->andReturn(false);

            $this->resolveRevisionService->shouldReceive('__invoke')->with($treeIsh)->andThrow($expected)->once();
            $this->printer->shouldReceive('stackTrace')->withArgs(expectEqualArg($expected))->once();

            $request = DiffIndexRequest::new($this->input);
            $useCase = new DiffIndexUseCase(
                $this->printer,
                $this->indexRepository,
                $this->objectRepository,
                $this->resolveRevisionService,
                $this->treeToFlatEntriesService,
                $this->diffIndexHelper
            );

            $actual = $useCase($request);

            expect($actual)->toBe(Result::InternalError);
        }
    );
});

describe('__invoke actionDefault', function () {
    it(
        'returns success action default on is same case and cached',
        function (
            string $treeIsh,
            IndexEntry $indexEntry,
            TreeEntry $treeEntry,
            string $target,
        ) {
            $this->input
                ->shouldReceive('getArgument')->with('tree-ish')->andReturn($treeIsh)
                ->shouldReceive('getOption')->with('cached')->andReturn(true)
                ->shouldReceive('getOption')->with('stat')->andReturn(false);

            $objectHash = ObjectHashFactory::new();
            $treeHash = ObjectHashFactory::new();
            $treeObject = TreeObjectFactory::new();
            $commitObject = CommitObject::new(
                $treeHash,
                GitSignatureFactory::new(),
                GitSignatureFactory::new(),
                'first commit',
                null
            );
            $index = GitIndex::new();
            $index->addEntry($indexEntry);
            $treeEntries = HashMap::new();
            $treeEntries->set($treeEntry->objectName, $treeEntry);

            $this->resolveRevisionService->shouldReceive('__invoke')->with($treeIsh)->andReturn($objectHash)->once();
            $this->objectRepository
                ->shouldReceive('exists')->withArgs(expectEqualArg($objectHash))->andReturn(true)->once()
                ->shouldReceive('getCommit')->withArgs(expectEqualArg($objectHash))->andReturn($commitObject)->once()
                ->shouldReceive('getTree')->withArgs(expectEqualArg($treeHash))->andReturn($treeObject)->once();
            $this->indexRepository->shouldReceive('getOrCreate')->andReturn($index)->once();
            $this->treeToFlatEntriesService->shouldReceive('__invoke')->withArgs(expectEqualArg($treeObject))->andReturn($treeEntries)->once();

            $this->diffIndexHelper
                ->shouldReceive('targetEntry')->withArgs(expectEqualArg(
                    $index->entries,
                    $treeEntries
                ))->andReturn($target)->once()
                ->shouldReceive('getOldStatusFromTree')->withArgs(expectEqualArg($treeEntry))->andReturn([
                    $treeEntry->gitFileMode,
                    $treeEntry->objectHash
                ])->once()
                ->shouldReceive('getNewStatusFromIndex')->withArgs(expectEqualArg($indexEntry))->andReturn([
                    $indexEntry->gitFileMode,
                    $indexEntry->objectHash
                ])->once()
                ->shouldReceive('isSame')->withArgs(expectEqualArg(
                    $treeEntry->gitFileMode,
                    $indexEntry->gitFileMode,
                    $treeEntry->objectHash,
                    $indexEntry->objectHash,
                ))->andReturn(true)->once()
                ->shouldReceive('nextTargetEntry')->withArgs(expectEqualArg(
                    $treeEntry,
                    $indexEntry,
                    $treeEntries,
                    $index->entries
                ))->andReturnNull()->once();

            $this->printer->shouldReceive('writeln')->never();

            $request = DiffIndexRequest::new($this->input);
            $useCase = new DiffIndexUseCase(
                $this->printer,
                $this->indexRepository,
                $this->objectRepository,
                $this->resolveRevisionService,
                $this->treeToFlatEntriesService,
                $this->diffIndexHelper
            );

            $actual = $useCase($request);

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            [
                'HEAD',
                IndexEntry::new(
                    FileStatFactory::default(),
                    ObjectHash::parse('f31073dbb2f8b075cc2e07735b6dff5b4f80b863'),
                    TrackedPath::parse('dummy')
                ),
                TreeEntry::new(
                    ObjectType::Blob,
                    GitFileMode::DefaultFile,
                    'dummy',
                    ObjectHash::parse('f31073dbb2f8b075cc2e07735b6dff5b4f80b863')
                ),
                'dummy',
            ],
        ]);

    it(
        'returns success action default on is added case and non cached',
        function (
            string $treeIsh,
            IndexEntry $indexEntry,
            string $target,
            string $expected
        ) {
            $this->input
                ->shouldReceive('getArgument')->with('tree-ish')->andReturn($treeIsh)
                ->shouldReceive('getOption')->with('cached')->andReturn(false)
                ->shouldReceive('getOption')->with('stat')->andReturn(false);

            $objectHash = ObjectHashFactory::new();
            $treeHash = ObjectHashFactory::new();
            $treeObject = TreeObjectFactory::new();
            $commitObject = CommitObject::new(
                $treeHash,
                GitSignatureFactory::new(),
                GitSignatureFactory::new(),
                'first commit',
                null
            );
            $index = GitIndex::new();
            $index->addEntry($indexEntry);
            $treeEntries = HashMap::new();

            $this->resolveRevisionService->shouldReceive('__invoke')->with($treeIsh)->andReturn($objectHash)->once();
            $this->objectRepository
                ->shouldReceive('exists')->withArgs(expectEqualArg($objectHash))->andReturn(true)->once()
                ->shouldReceive('getCommit')->withArgs(expectEqualArg($objectHash))->andReturn($commitObject)->once()
                ->shouldReceive('getTree')->withArgs(expectEqualArg($treeHash))->andReturn($treeObject)->once();
            $this->indexRepository->shouldReceive('getOrCreate')->andReturn($index)->once();
            $this->treeToFlatEntriesService->shouldReceive('__invoke')->withArgs(expectEqualArg($treeObject))->andReturn($treeEntries)->once();

            $this->diffIndexHelper
                ->shouldReceive('targetEntry')->withArgs(expectEqualArg(
                    $index->entries,
                    $treeEntries
                ))->andReturn($target)->once()
                ->shouldReceive('getOldStatusFromTree')->with(null)->andReturn([
                    GitFileMode::Unknown,
                    ObjectHash::zero(),
                ])->once()
                ->shouldReceive('getNewStatusFromWorktree')->withArgs(expectEqualArg($indexEntry))->andReturn([
                    $indexEntry->gitFileMode,
                    $indexEntry->objectHash
                ])->once()
                ->shouldReceive('isSame')->withArgs(expectEqualArg(
                    GitFileMode::Unknown,
                    $indexEntry->gitFileMode,
                    ObjectHash::zero(),
                    $indexEntry->objectHash,
                ))->andReturn(false)->once()
                ->shouldReceive('isAdded')->withArgs(expectEqualArg(
                    GitFileMode::Unknown,
                    ObjectHash::zero(),
                ))->andReturn(true)->once()
                ->shouldReceive('nextTargetEntry')->withArgs(expectEqualArg(
                    null,
                    $indexEntry,
                    $treeEntries,
                    $index->entries
                ))->andReturnNull()->once();

            $this->printer->shouldReceive('writeln')->with($expected)->once();

            $request = DiffIndexRequest::new($this->input);
            $useCase = new DiffIndexUseCase(
                $this->printer,
                $this->indexRepository,
                $this->objectRepository,
                $this->resolveRevisionService,
                $this->treeToFlatEntriesService,
                $this->diffIndexHelper
            );

            $actual = $useCase($request);

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            [
                'HEAD',
                IndexEntry::new(
                    FileStatFactory::default(),
                    ObjectHash::parse('f31073dbb2f8b075cc2e07735b6dff5b4f80b863'),
                    TrackedPath::parse('dummy')
                ),
                'dummy',
                ":000000 100644 0000000000000000000000000000000000000000 f31073dbb2f8b075cc2e07735b6dff5b4f80b863 A\tdummy"
            ],
        ]);

    it(
        'returns success action default on is modefied case and non cached',
        function (
            string $treeIsh,
            IndexEntry $indexEntry,
            TreeEntry $treeEntry,
            string $target,
            string $expected
        ) {
            $this->input
                ->shouldReceive('getArgument')->with('tree-ish')->andReturn($treeIsh)
                ->shouldReceive('getOption')->with('cached')->andReturn(false)
                ->shouldReceive('getOption')->with('stat')->andReturn(false);

            $objectHash = ObjectHashFactory::new();
            $treeHash = ObjectHashFactory::new();
            $treeObject = TreeObjectFactory::new();
            $commitObject = CommitObject::new(
                $treeHash,
                GitSignatureFactory::new(),
                GitSignatureFactory::new(),
                'first commit',
                null
            );
            $index = GitIndex::new();
            $index->addEntry($indexEntry);
            $treeEntries = HashMap::new();
            $treeEntries->set($treeEntry->objectName, $treeEntry);

            $this->resolveRevisionService->shouldReceive('__invoke')->with($treeIsh)->andReturn($objectHash)->once();
            $this->objectRepository
                ->shouldReceive('exists')->withArgs(expectEqualArg($objectHash))->andReturn(true)->once()
                ->shouldReceive('getCommit')->withArgs(expectEqualArg($objectHash))->andReturn($commitObject)->once()
                ->shouldReceive('getTree')->withArgs(expectEqualArg($treeHash))->andReturn($treeObject)->once();
            $this->indexRepository->shouldReceive('getOrCreate')->andReturn($index)->once();
            $this->treeToFlatEntriesService->shouldReceive('__invoke')->withArgs(expectEqualArg($treeObject))->andReturn($treeEntries)->once();

            $this->diffIndexHelper
                ->shouldReceive('targetEntry')->withArgs(expectEqualArg(
                    $index->entries,
                    $treeEntries
                ))->andReturn($target)->once()
                ->shouldReceive('getOldStatusFromTree')->withArgs(expectEqualArg($treeEntry))->andReturn([
                    $treeEntry->gitFileMode,
                    $treeEntry->objectHash
                ])->once()
                ->shouldReceive('getNewStatusFromWorktree')->withArgs(expectEqualArg($indexEntry))->andReturn([
                    $indexEntry->gitFileMode,
                    $indexEntry->objectHash
                ])->once()
                ->shouldReceive('isSame')->withArgs(expectEqualArg(
                    $treeEntry->gitFileMode,
                    $indexEntry->gitFileMode,
                    $treeEntry->objectHash,
                    $indexEntry->objectHash,
                ))->andReturn(false)->once()
                ->shouldReceive('isAdded')->withArgs(expectEqualArg(
                    $treeEntry->gitFileMode,
                    $treeEntry->objectHash,
                ))->andReturn(false)->once()
                ->shouldReceive('isModefied')->withArgs(expectEqualArg(
                    $treeEntry->gitFileMode,
                    $treeEntry->objectHash,
                    $indexEntry->gitFileMode,
                    $indexEntry->objectHash,
                ))->andReturn(true)->once()
                ->shouldReceive('nextTargetEntry')->withArgs(expectEqualArg(
                    $treeEntry,
                    $indexEntry,
                    $treeEntries,
                    $index->entries
                ))->andReturnNull()->once();

            $this->printer->shouldReceive('writeln')->with($expected)->once();

            $request = DiffIndexRequest::new($this->input);
            $useCase = new DiffIndexUseCase(
                $this->printer,
                $this->indexRepository,
                $this->objectRepository,
                $this->resolveRevisionService,
                $this->treeToFlatEntriesService,
                $this->diffIndexHelper
            );

            $actual = $useCase($request);

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            [
                'HEAD',
                IndexEntry::new(
                    FileStatFactory::default(),
                    ObjectHash::parse('f31073dbb2f8b075cc2e07735b6dff5b4f80b863'),
                    TrackedPath::parse('dummy1')
                ),
                TreeEntry::new(
                    ObjectType::Blob,
                    GitFileMode::DefaultFile,
                    'dummy1',
                    ObjectHash::parse('bf9661defa3daecacfde5bde0214c4a439351d4d')
                ),
                'dummy1',
                ":100644 100644 bf9661defa3daecacfde5bde0214c4a439351d4d f31073dbb2f8b075cc2e07735b6dff5b4f80b863 M\tdummy1"
            ],
            [
                'main',
                IndexEntry::new(
                    FileStatFactory::exec(),
                    ObjectHash::parse('bf9661defa3daecacfde5bde0214c4a439351d4d'),
                    TrackedPath::parse('dummy2')
                ),
                TreeEntry::new(
                    ObjectType::Blob,
                    GitFileMode::DefaultFile,
                    'dummy2',
                    ObjectHash::parse('bf9661defa3daecacfde5bde0214c4a439351d4d')
                ),
                'dummy2',
                ":100644 100755 bf9661defa3daecacfde5bde0214c4a439351d4d bf9661defa3daecacfde5bde0214c4a439351d4d M\tdummy2"
            ],
        ]);

    it(
        'returns success action default on is deleted case and non cached',
        function (
            string $treeIsh,
            TreeEntry $treeEntry,
            string $target,
            string $expected
        ) {
            $this->input
                ->shouldReceive('getArgument')->with('tree-ish')->andReturn($treeIsh)
                ->shouldReceive('getOption')->with('cached')->andReturn(false)
                ->shouldReceive('getOption')->with('stat')->andReturn(false);

            $objectHash = ObjectHashFactory::new();
            $treeHash = ObjectHashFactory::new();
            $treeObject = TreeObjectFactory::new();
            $commitObject = CommitObject::new(
                $treeHash,
                GitSignatureFactory::new(),
                GitSignatureFactory::new(),
                'first commit',
                null
            );
            $index = GitIndex::new();
            $treeEntries = HashMap::new();
            $treeEntries->set($treeEntry->objectName, $treeEntry);

            $this->resolveRevisionService->shouldReceive('__invoke')->with($treeIsh)->andReturn($objectHash)->once();
            $this->objectRepository
                ->shouldReceive('exists')->withArgs(expectEqualArg($objectHash))->andReturn(true)->once()
                ->shouldReceive('getCommit')->withArgs(expectEqualArg($objectHash))->andReturn($commitObject)->once()
                ->shouldReceive('getTree')->withArgs(expectEqualArg($treeHash))->andReturn($treeObject)->once();
            $this->indexRepository->shouldReceive('getOrCreate')->andReturn($index)->once();
            $this->treeToFlatEntriesService->shouldReceive('__invoke')->withArgs(expectEqualArg($treeObject))->andReturn($treeEntries)->once();

            $this->diffIndexHelper
                ->shouldReceive('targetEntry')->withArgs(expectEqualArg(
                    $index->entries,
                    $treeEntries
                ))->andReturn($target)->once()
                ->shouldReceive('getOldStatusFromTree')->withArgs(expectEqualArg($treeEntry))->andReturn([
                    $treeEntry->gitFileMode,
                    $treeEntry->objectHash
                ])->once()
                ->shouldReceive('getNewStatusFromWorktree')->with(null)->andReturn([
                    GitFileMode::Unknown,
                    ObjectHash::zero(),
                ])->once()
                ->shouldReceive('isSame')->withArgs(expectEqualArg(
                    $treeEntry->gitFileMode,
                    GitFileMode::Unknown,
                    $treeEntry->objectHash,
                    ObjectHash::zero(),
                ))->andReturn(false)->once()
                ->shouldReceive('isAdded')->withArgs(expectEqualArg(
                    $treeEntry->gitFileMode,
                    $treeEntry->objectHash,
                ))->andReturn(false)->once()
                ->shouldReceive('isModefied')->withArgs(expectEqualArg(
                    $treeEntry->gitFileMode,
                    $treeEntry->objectHash,
                    GitFileMode::Unknown,
                    ObjectHash::zero(),
                ))->andReturn(false)->once()
                ->shouldReceive('isDeleted')->withArgs(expectEqualArg(
                    GitFileMode::Unknown,
                    ObjectHash::zero(),
                ))->andReturn(true)->once()
                ->shouldReceive('nextTargetEntry')->withArgs(expectEqualArg(
                    $treeEntry,
                    null,
                    $treeEntries,
                    $index->entries
                ))->andReturnNull()->once();

            $this->printer->shouldReceive('writeln')->with($expected)->once();

            $request = DiffIndexRequest::new($this->input);
            $useCase = new DiffIndexUseCase(
                $this->printer,
                $this->indexRepository,
                $this->objectRepository,
                $this->resolveRevisionService,
                $this->treeToFlatEntriesService,
                $this->diffIndexHelper
            );

            $actual = $useCase($request);

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            [
                'HEAD',
                TreeEntry::new(
                    ObjectType::Blob,
                    GitFileMode::DefaultFile,
                    'dummy1',
                    ObjectHash::parse('bf9661defa3daecacfde5bde0214c4a439351d4d')
                ),
                'dummy1',
                ":100644 000000 bf9661defa3daecacfde5bde0214c4a439351d4d 0000000000000000000000000000000000000000 D\tdummy1"
            ],
        ]);
});

describe('__invoke actionStat', function () {
    it(
        'returns success action stat on cached and changed contents',
        function (
            string $treeIsh,
            string $target,
            string $nextTarget,
            array $targets,
            int $maxPathLen,
            int $maxDiffDigits,
            string $expected
        ) {
            $this->input
                ->shouldReceive('getArgument')->with('tree-ish')->andReturn($treeIsh)
                ->shouldReceive('getOption')->with('cached')->andReturn(true)
                ->shouldReceive('getOption')->with('stat')->andReturn(true);

            $objectHash = ObjectHashFactory::new();
            $treeHash = ObjectHashFactory::new();
            $treeObject = TreeObjectFactory::new();
            $commitObject = CommitObject::new(
                $treeHash,
                GitSignatureFactory::new(),
                GitSignatureFactory::new(),
                'first commit',
                null
            );
            $index = GitIndex::new();
            $treeEntries = HashMap::new();
            foreach ($targets as $t) {
                $index->addEntry($t['indexEntry']);
                $treeEntries->set($t['treeEntry']->objectName, $t['treeEntry']);
            }

            $this->resolveRevisionService->shouldReceive('__invoke')->with($treeIsh)->andReturn($objectHash)->once();
            $this->objectRepository
                ->shouldReceive('exists')->withArgs(expectEqualArg($objectHash))->andReturn(true)->once()
                ->shouldReceive('getCommit')->withArgs(expectEqualArg($objectHash))->andReturn($commitObject)->once()
                ->shouldReceive('getTree')->withArgs(expectEqualArg($treeHash))->andReturn($treeObject)->once();
            $this->indexRepository->shouldReceive('getOrCreate')->andReturn($index)->once();
            $this->treeToFlatEntriesService->shouldReceive('__invoke')->withArgs(expectEqualArg($treeObject))->andReturn($treeEntries)->once();

            $this->diffIndexHelper
                ->shouldReceive('targetEntry')->withArgs(expectEqualArg(
                    $index->entries,
                    $treeEntries
                ))->andReturn($target)->once()
                ->shouldReceive('nextTargetEntry')->withArgs(expectEqualArg(
                    $targets[0]['treeEntry'],
                    $targets[0]['indexEntry'],
                    $treeEntries,
                    $index->entries
                ))->andReturn($nextTarget)->once()
                ->shouldReceive('nextTargetEntry')->withArgs(expectEqualArg(
                    $targets[1]['treeEntry'],
                    $targets[1]['indexEntry'],
                    $treeEntries,
                    $index->entries
                ))->andReturn(null)->once();

            foreach ($targets as $t) {
                $this->diffIndexHelper
                    ->shouldReceive('getOldContentsFromTree')->withArgs(expectEqualArg($t['treeEntry']))->andReturn($t['treeContents'])->once()
                    ->shouldReceive('getNewContentsFromIndex')->withArgs(expectEqualArg($t['indexEntry']))->andReturn($t['indexContents'])->once()
                    ->shouldReceive('countDiff')->withArgs(expectEqualArg(
                        $t['treeContents'],
                        $t['indexContents'],
                        $t['target']
                    ))->andReturn($t['diffStat'])->once();
                $this->printer->shouldReceive('writelnDiffStat')->with($maxPathLen, $maxDiffDigits, $t['path'], $t['insertions'], $t['deletions'])->once();
            }

            $this->printer->shouldReceive('writeln')->with($expected)->once();

            $request = DiffIndexRequest::new($this->input);
            $useCase = new DiffIndexUseCase(
                $this->printer,
                $this->indexRepository,
                $this->objectRepository,
                $this->resolveRevisionService,
                $this->treeToFlatEntriesService,
                $this->diffIndexHelper
            );

            $actual = $useCase($request);

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            function () {
                $diffStat1 = DiffStat::new('dummy1');
                for ($i = 0; $i < 2; $i++) {
                    $diffStat1->insert();
                };
                for ($i = 0; $i < 3; $i++) {
                    $diffStat1->delete();
                };

                $diffStat2 = DiffStat::new('dummy2-');
                for ($i = 0; $i < 1; $i++) {
                    $diffStat2->insert();
                };
                for ($i = 0; $i < 10; $i++) {
                    $diffStat2->delete();
                };

                return [
                    'treeIsh' => 'HEAD',
                    'target' => 'dummy1',
                    'nextTarget' => 'dummy2-',
                    'targets' => [
                        [
                            'target' => 'dummy1',
                            'indexEntry' => IndexEntry::new(
                                FileStatFactory::default(),
                                ObjectHash::parse('f31073dbb2f8b075cc2e07735b6dff5b4f80b863'),
                                TrackedPath::parse('dummy1')
                            ),
                            'treeEntry' => TreeEntry::new(
                                ObjectType::Blob,
                                GitFileMode::DefaultFile,
                                'dummy1',
                                ObjectHash::parse('bf9661defa3daecacfde5bde0214c4a439351d4d')
                            ),
                            'treeContents' => "contents\ndelete-line1\ndelete-line2\ndelete-line3\n",
                            'indexContents' => "contents\nnew-line1\nnew-line2\n",
                            'diffStat' => $diffStat1,
                            'path' => 'dummy1',
                            'insertions' => 2,
                            'deletions' => 3,
                        ],
                        [
                            'target' => 'dummy2-',
                            'indexEntry' => IndexEntry::new(
                                FileStatFactory::default(),
                                ObjectHash::parse('f31073dbb2f8b075cc2e07735b6dff5b4f80b864'),
                                TrackedPath::parse('dummy2-')
                            ),
                            'treeEntry' => TreeEntry::new(
                                ObjectType::Blob,
                                GitFileMode::DefaultFile,
                                'dummy2-',
                                ObjectHash::parse('bf9661defa3daecacfde5bde0214c4a439351d4e')
                            ),
                            'treeContents' => "contents\nd1\nd1\nd1\nd1\nd1\nd1\nd1\nd1\nd1\nd1\n",
                            'indexContents' => "contents\nn1\n",
                            'diffStat' => $diffStat2,
                            'path' => 'dummy2-',
                            'insertions' => 1,
                            'deletions' => 10,
                        ],
                    ],
                    'maxPathLen' => 7,
                    'maxDiffDigits' => 2,
                    'expected' => ' 2 files changed, 3 insertions(+), 13 deletions(-)',
                ];
            }
        ]);

    it(
        'returns success action stat on non cached',
        function (
            string $treeIsh,
            IndexEntry $indexEntry,
            TreeEntry $treeEntry,
            string $target,
            string $treeContents,
            string $indexContents,
            DiffStat $diffStat,
        ) {
            $this->input
                ->shouldReceive('getArgument')->with('tree-ish')->andReturn($treeIsh)
                ->shouldReceive('getOption')->with('cached')->andReturn(false)
                ->shouldReceive('getOption')->with('stat')->andReturn(true);

            $objectHash = ObjectHashFactory::new();
            $treeHash = ObjectHashFactory::new();
            $treeObject = TreeObjectFactory::new();
            $commitObject = CommitObject::new(
                $treeHash,
                GitSignatureFactory::new(),
                GitSignatureFactory::new(),
                'first commit',
                null
            );
            $index = GitIndex::new();
            $index->addEntry($indexEntry);
            $treeEntries = HashMap::new();
            $treeEntries->set($treeEntry->objectName, $treeEntry);

            $this->resolveRevisionService->shouldReceive('__invoke')->with($treeIsh)->andReturn($objectHash)->once();
            $this->objectRepository
                ->shouldReceive('exists')->withArgs(expectEqualArg($objectHash))->andReturn(true)->once()
                ->shouldReceive('getCommit')->withArgs(expectEqualArg($objectHash))->andReturn($commitObject)->once()
                ->shouldReceive('getTree')->withArgs(expectEqualArg($treeHash))->andReturn($treeObject)->once();
            $this->indexRepository->shouldReceive('getOrCreate')->andReturn($index)->once();
            $this->treeToFlatEntriesService->shouldReceive('__invoke')->withArgs(expectEqualArg($treeObject))->andReturn($treeEntries)->once();

            $this->diffIndexHelper
                ->shouldReceive('targetEntry')->withArgs(expectEqualArg(
                    $index->entries,
                    $treeEntries
                ))->andReturn($target)->once()
                ->shouldReceive('getOldContentsFromTree')->withArgs(expectEqualArg($treeEntry))->andReturn($treeContents)->once()
                ->shouldReceive('getNewContentsFromWorktree')->withArgs(expectEqualArg($indexEntry))->andReturn($indexContents)->once()
                ->shouldReceive('countDiff')->withArgs(expectEqualArg(
                    $treeContents,
                    $indexContents,
                    $target
                ))->andReturn($diffStat)->once()
                ->shouldReceive('nextTargetEntry')->withArgs(expectEqualArg(
                    $treeEntry,
                    $indexEntry,
                    $treeEntries,
                    $index->entries
                ))->andReturnNull()->once();

            $this->printer
                ->shouldReceive('writelnDiffStat')->never()
                ->shouldReceive('writeln')->never();

            $request = DiffIndexRequest::new($this->input);
            $useCase = new DiffIndexUseCase(
                $this->printer,
                $this->indexRepository,
                $this->objectRepository,
                $this->resolveRevisionService,
                $this->treeToFlatEntriesService,
                $this->diffIndexHelper
            );

            $actual = $useCase($request);

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            fn() => [
                'treeIsh' => 'HEAD',
                'indexEntry' => IndexEntry::new(
                    FileStatFactory::default(),
                    ObjectHash::parse('f31073dbb2f8b075cc2e07735b6dff5b4f80b863'),
                    TrackedPath::parse('dummy1')
                ),
                'treeEntry' => TreeEntry::new(
                    ObjectType::Blob,
                    GitFileMode::DefaultFile,
                    'dummy1',
                    ObjectHash::parse('bf9661defa3daecacfde5bde0214c4a439351d4d')
                ),
                'target' => 'dummy1',
                'treeContents' => "contents\nnew-line1\nnew-line2\n",
                'indexContents' => "contents\ndelete-line1\ndelete-line2\ndelete-line3\n",
                DiffStat::new('dummy1'),
            ]
        ]);
});
