<?php

declare(strict_types=1);

use Phpgit\Command\CommandInterface;
use Phpgit\Domain\DiffStat;
use Phpgit\Domain\GitFileMode;
use Phpgit\Domain\GitIndex;
use Phpgit\Domain\HashMap;
use Phpgit\Domain\HeadType;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\ObjectType;
use Phpgit\Domain\Printer\PrinterInterface;
use Phpgit\Domain\Reference;
use Phpgit\Domain\ReferenceType;
use Phpgit\Domain\Repository\IndexRepositoryInterface;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\Repository\RefRepositoryInterface;
use Phpgit\Domain\Result;
use Phpgit\Domain\TrackedPath;
use Phpgit\Domain\TreeEntry;
use Phpgit\Helper\DiffIndexHelperInterface;
use Phpgit\Request\CommitRequest;
use Phpgit\Service\CreateCommitTreeServiceInterface;
use Phpgit\Service\CreateSegmentTreeServiceInterface;
use Phpgit\Service\ResolveRevisionServiceInterface;
use Phpgit\Service\SaveTreeObjectServiceInterface;
use Phpgit\Service\TreeToFlatEntriesServiceInterface;
use Phpgit\UseCase\CommitUseCase;
use Symfony\Component\Console\Input\InputInterface;
use Tests\Factory\CommitObjectFactory;
use Tests\Factory\FileStatFactory;
use Tests\Factory\GitIndexFactory;
use Tests\Factory\ObjectHashFactory;
use Tests\Factory\SegmentTreeFactory;
use Tests\Factory\TreeObjectFactory;

beforeAll(function () {
    $command = Mockery::mock(CommandInterface::class);
    $command->shouldReceive('addOption');
    CommitRequest::setUp($command);
});

beforeEach(function () {
    $this->input = Mockery::mock(InputInterface::class);
    $this->printer = Mockery::mock(PrinterInterface::class);
    $this->indexRepository = Mockery::mock(IndexRepositoryInterface::class);
    $this->refRepository = Mockery::mock(RefRepositoryInterface::class);
    $this->objectRepository = Mockery::mock(ObjectRepositoryInterface::class);
    $this->resolveRevisionService = Mockery::mock(ResolveRevisionServiceInterface::class);
    $this->treeToFlatEntriesService = Mockery::mock(TreeToFlatEntriesServiceInterface::class);
    $this->createSegmentTreeService = Mockery::mock(CreateSegmentTreeServiceInterface::class);
    $this->saveTreeObjectService = Mockery::mock(SaveTreeObjectServiceInterface::class);
    $this->createCommitTreeService = Mockery::mock(CreateCommitTreeServiceInterface::class);
    $this->diffIndexHelper = Mockery::mock(DiffIndexHelperInterface::class);
});

describe('__invoke', function () {
    it(
        'returns success and outputs commit details, on first commit and head-type is hash',
        function (
            string $message,
            string $target,
            IndexEntry $indexEntry,
            string $indexContents,
            DiffStat $diffStat,
            ObjectHash $commitHash,
            string $expectedCommitMessage,
            string $expectedSummary,
            array $expectedHistories
        ) {
            $this->input->shouldReceive('getOption')->with('message')->andReturn($message);

            $headHash = ObjectHashFactory::random();
            $segmentTree = SegmentTreeFactory::new();
            $treeHash = ObjectHashFactory::random();
            $commitObject = CommitObjectFactory::new();

            $index = GitIndex::new();
            $index->addEntry($indexEntry);

            $treeEntries = HashMap::new();

            $this->resolveRevisionService->shouldReceive('__invoke')->andReturnNull()->once();
            $this->indexRepository->shouldReceive('getOrCreate')->andReturn($index)->once();
            $this->refRepository
                ->shouldReceive('headType')->andReturn(HeadType::Hash)->once()
                ->shouldReceive('resolveHead')->andReturn($headHash)->once();
            $this->objectRepository->shouldReceive('getTree')->never();

            // diffIndex
            $this->diffIndexHelper
                ->shouldReceive('targetEntry')->withArgs(expectEqualArg(
                    $index->entries,
                    $treeEntries
                ))->andReturn($target)->once()
                ->shouldReceive('getOldStatusFromTree')->with(null)->andReturn([GitFileMode::Unknown, ObjectHash::zero()])->once()
                ->shouldReceive('getNewStatusFromIndex')->withArgs(expectEqualArg($indexEntry))->andReturn([$indexEntry->gitFileMode, $indexEntry->objectHash])->once()
                ->shouldReceive('getOldContentsFromTree')->with(null)->andReturnNull()->once()
                ->shouldReceive('getNewContentsFromIndex')->withArgs(expectEqualArg($indexEntry))->andReturn($indexContents)->once()
                ->shouldReceive('countDiff')->withArgs(expectEqualArg(
                    null,
                    $indexContents,
                    $target
                ))->andReturn($diffStat)->once()
                ->shouldReceive('nextTargetEntry')->withArgs(expectEqualArg(
                    null,
                    $indexEntry,
                    $treeEntries,
                    $index->entries
                ))->andReturnNull()->once()
                ->shouldReceive('isSame')->withArgs(expectEqualArg(GitFileMode::Unknown, $indexEntry->gitFileMode, ObjectHash::zero(), $indexEntry->objectHash))->andReturn(false)->once()
                ->shouldReceive('isAdded')->withArgs(expectEqualArg(GitFileMode::Unknown, ObjectHash::zero()))->andReturn(true)->once();

            $this->createSegmentTreeService->shouldReceive('__invoke')->withArgs(expectEqualArg($index))->andReturn($segmentTree)->once();
            $this->saveTreeObjectService->shouldReceive('__invoke')->withArgs(expectEqualArg($segmentTree))->andReturn($treeHash)->once();
            $this->createCommitTreeService->shouldReceive('__invoke')->withArgs(expectEqualArg($treeHash, $message, null))->andReturn($commitObject)->once();
            $this->objectRepository->shouldReceive('save')->withArgs(expectEqualArg($commitObject))->andReturn($commitHash)->once();
            $this->refRepository->shouldReceive('updateHead')->withArgs(expectEqualArg($commitHash))->once();
            $this->printer
                ->shouldReceive('writeln')->with($expectedCommitMessage)->once()
                ->shouldReceive('writeln')->with($expectedSummary)->once()
                ->shouldReceive('writeln')->withArgs(expectEqualArg($expectedHistories))->once();

            $request = CommitRequest::new($this->input);
            $useCase = new CommitUseCase(
                $this->printer,
                $this->indexRepository,
                $this->refRepository,
                $this->objectRepository,
                $this->resolveRevisionService,
                $this->treeToFlatEntriesService,
                $this->createSegmentTreeService,
                $this->saveTreeObjectService,
                $this->createCommitTreeService,
                $this->diffIndexHelper
            );
            $actual = $useCase($request);

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            function () {
                $diffStat = DiffStat::new('file1');
                $diffStat->addedFile();
                $diffStat->insert();

                return [
                    'message' => 'first commit',
                    'target' => 'file1',
                    'indexEntry' => IndexEntry::new(
                        FileStatFactory::default(),
                        ObjectHash::parse('60b27f004e454aca81b0480209cce5081ec52390'),
                        TrackedPath::parse('file1')
                    ),
                    'indexContents' => "new file\n",
                    'diffStat' => $diffStat,
                    'commitHash' => ObjectHash::parse('397c3f85532053c1abdd7d9f9d44f783ef6aef11'),
                    'expectedCommitMessage' => '[detached HEAD 397c3f8] first commit',
                    'expectedSummary' => ' 1 files changed, 1 insertions(+), 0 deletions(-)',
                    'expectedHistories' => [' create mode 100644 file1'],
                ];
            }
        ]);

    it(
        'returns success and outputs commit details, on first commit and head-type is reference',
        function (
            string $message,
            Reference $headRef,
            string $target,
            IndexEntry $indexEntry,
            string $indexContents,
            DiffStat $diffStat,
            ObjectHash $commitHash,
            string $expectedCommitMessage,
            string $expectedSummary,
            array $expectedHistories
        ) {
            $this->input->shouldReceive('getOption')->with('message')->andReturn($message);

            $segmentTree = SegmentTreeFactory::new();
            $treeHash = ObjectHashFactory::random();
            $commitObject = CommitObjectFactory::new();

            $index = GitIndex::new();
            $index->addEntry($indexEntry);

            $treeEntries = HashMap::new();

            $this->resolveRevisionService->shouldReceive('__invoke')->andReturnNull()->once();
            $this->indexRepository->shouldReceive('getOrCreate')->andReturn($index)->once();
            $this->refRepository
                ->shouldReceive('headType')->andReturn(HeadType::Reference)->once()
                ->shouldReceive('head')->andReturn($headRef)->times(2); // call in currentHeadLabel and updateRef
            $this->objectRepository->shouldReceive('getTree')->never();

            // diffIndex
            $this->diffIndexHelper
                ->shouldReceive('targetEntry')->withArgs(expectEqualArg(
                    $index->entries,
                    $treeEntries
                ))->andReturn($target)->once()
                ->shouldReceive('getOldStatusFromTree')->with(null)->andReturn([GitFileMode::Unknown, ObjectHash::zero()])->once()
                ->shouldReceive('getNewStatusFromIndex')->withArgs(expectEqualArg($indexEntry))->andReturn([$indexEntry->gitFileMode, $indexEntry->objectHash])->once()
                ->shouldReceive('getOldContentsFromTree')->with(null)->andReturnNull()->once()
                ->shouldReceive('getNewContentsFromIndex')->withArgs(expectEqualArg($indexEntry))->andReturn($indexContents)->once()
                ->shouldReceive('countDiff')->withArgs(expectEqualArg(
                    null,
                    $indexContents,
                    $target
                ))->andReturn($diffStat)->once()
                ->shouldReceive('nextTargetEntry')->withArgs(expectEqualArg(
                    null,
                    $indexEntry,
                    $treeEntries,
                    $index->entries
                ))->andReturnNull()->once()
                ->shouldReceive('isSame')->withArgs(expectEqualArg(GitFileMode::Unknown, $indexEntry->gitFileMode, ObjectHash::zero(), $indexEntry->objectHash))->andReturn(false)->once()
                ->shouldReceive('isAdded')->withArgs(expectEqualArg(GitFileMode::Unknown, ObjectHash::zero()))->andReturn(true)->once();

            $this->createSegmentTreeService->shouldReceive('__invoke')->withArgs(expectEqualArg($index))->andReturn($segmentTree)->once();
            $this->saveTreeObjectService->shouldReceive('__invoke')->withArgs(expectEqualArg($segmentTree))->andReturn($treeHash)->once();
            $this->createCommitTreeService->shouldReceive('__invoke')->withArgs(expectEqualArg($treeHash, $message, null))->andReturn($commitObject)->once();
            $this->objectRepository->shouldReceive('save')->withArgs(expectEqualArg($commitObject))->andReturn($commitHash)->once();
            $this->refRepository->shouldReceive('createOrUpdate')->withArgs(expectEqualArg($headRef, $commitHash))->once();
            $this->printer
                ->shouldReceive('writeln')->with($expectedCommitMessage)->once()
                ->shouldReceive('writeln')->with($expectedSummary)->once()
                ->shouldReceive('writeln')->withArgs(expectEqualArg($expectedHistories))->once();

            $request = CommitRequest::new($this->input);
            $useCase = new CommitUseCase(
                $this->printer,
                $this->indexRepository,
                $this->refRepository,
                $this->objectRepository,
                $this->resolveRevisionService,
                $this->treeToFlatEntriesService,
                $this->createSegmentTreeService,
                $this->saveTreeObjectService,
                $this->createCommitTreeService,
                $this->diffIndexHelper
            );
            $actual = $useCase($request);

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            function () {
                $diffStat = DiffStat::new('file1');
                $diffStat->addedFile();
                $diffStat->insert();

                return [
                    'message' => 'first commit',
                    'headRef' => Reference::new(ReferenceType::Local, 'main'),
                    'target' => 'file1',
                    'indexEntry' => IndexEntry::new(
                        FileStatFactory::default(),
                        ObjectHash::parse('60b27f004e454aca81b0480209cce5081ec52390'),
                        TrackedPath::parse('file1')
                    ),
                    'indexContents' => "new file\n",
                    'diffStat' => $diffStat,
                    'commitHash' => ObjectHash::parse('397c3f85532053c1abdd7d9f9d44f783ef6aef11'),
                    'expectedCommitMessage' => '[main (root-commit) 397c3f8] first commit',
                    'expectedSummary' => ' 1 files changed, 1 insertions(+), 0 deletions(-)',
                    'expectedHistories' => [' create mode 100644 file1'],
                ];
            }
        ]);

    it(
        'returns success and outputs commit details, on second commit and head-type is reference',
        function (
            string $message,
            Reference $headRef,
            string $target,
            TreeEntry $treeEntry,
            IndexEntry $indexEntry,
            string $treeContents,
            string $indexContents,
            DiffStat $diffStat,
            ObjectHash $commitHash,
            string $expectedCommitMessage,
            string $expectedSummary,
            array $expectedHistories
        ) {
            $this->input->shouldReceive('getOption')->with('message')->andReturn($message);

            $parentCommitHash = ObjectHashFactory::random();
            $parentCommit = CommitObjectFactory::new();
            $treeObject = TreeObjectFactory::new();
            $segmentTree = SegmentTreeFactory::new();
            $treeHash = ObjectHashFactory::random();
            $commitObject = CommitObjectFactory::new();

            $index = GitIndex::new();
            $index->addEntry($indexEntry);

            $treeEntries = HashMap::new();
            $treeEntries->set($treeEntry->objectName, $treeEntry);

            $this->resolveRevisionService->shouldReceive('__invoke')->andReturn($parentCommitHash)->once();
            $this->objectRepository->shouldReceive('getCommit')->withArgs(expectEqualArg($parentCommitHash))->andReturn($parentCommit)->once();
            $this->indexRepository->shouldReceive('getOrCreate')->andReturn($index)->once();
            $this->refRepository
                ->shouldReceive('headType')->andReturn(HeadType::Reference)->once()
                ->shouldReceive('head')->andReturn($headRef)->times(2); // call in currentHeadLabel and updateRef
            $this->objectRepository->shouldReceive('getTree')->withArgs(expectEqualArg($parentCommit->treeHash()))->andReturn($treeObject)->once();
            $this->treeToFlatEntriesService->shouldReceive('__invoke')->withArgs(expectEqualArg($treeObject))->andReturn($treeEntries)->once();

            // diffIndex
            $this->diffIndexHelper
                ->shouldReceive('targetEntry')->withArgs(expectEqualArg(
                    $index->entries,
                    $treeEntries
                ))->andReturn($target)->once()
                ->shouldReceive('getOldStatusFromTree')->withArgs(expectEqualArg($treeEntry))->andReturn([$treeEntry->gitFileMode, $treeEntry->objectHash])->once()
                ->shouldReceive('getNewStatusFromIndex')->withArgs(expectEqualArg($indexEntry))->andReturn([$indexEntry->gitFileMode, $indexEntry->objectHash])->once()
                ->shouldReceive('getOldContentsFromTree')->withArgs(expectEqualArg($treeEntry))->andReturn($treeContents)->once()
                ->shouldReceive('getNewContentsFromIndex')->withArgs(expectEqualArg($indexEntry))->andReturn($indexContents)->once()
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
                ))->andReturnNull()->once()
                ->shouldReceive('isSame')->withArgs(expectEqualArg($treeEntry->gitFileMode, $indexEntry->gitFileMode, $treeEntry->objectHash, $indexEntry->objectHash))->andReturn(false)->once()
                ->shouldReceive('isAdded')->withArgs(expectEqualArg($treeEntry->gitFileMode, $treeEntry->objectHash))->andReturn(false)->once()
                ->shouldReceive('isModefied')->withArgs(expectEqualArg($treeEntry->gitFileMode, $treeEntry->objectHash, $indexEntry->gitFileMode, $indexEntry->objectHash))->andReturn(true)->once();

            $this->createSegmentTreeService->shouldReceive('__invoke')->withArgs(expectEqualArg($index))->andReturn($segmentTree)->once();
            $this->saveTreeObjectService->shouldReceive('__invoke')->withArgs(expectEqualArg($segmentTree))->andReturn($treeHash)->once();
            $this->createCommitTreeService->shouldReceive('__invoke')->withArgs(expectEqualArg($treeHash, $message, $parentCommitHash))->andReturn($commitObject)->once();
            $this->objectRepository->shouldReceive('save')->withArgs(expectEqualArg($commitObject))->andReturn($commitHash)->once();
            $this->refRepository->shouldReceive('createOrUpdate')->withArgs(expectEqualArg($headRef, $commitHash))->once();
            $this->printer
                ->shouldReceive('writeln')->with($expectedCommitMessage)->once()
                ->shouldReceive('writeln')->with($expectedSummary)->once()
                ->shouldReceive('writeln')->withArgs(expectEqualArg($expectedHistories))->once();

            $request = CommitRequest::new($this->input);
            $useCase = new CommitUseCase(
                $this->printer,
                $this->indexRepository,
                $this->refRepository,
                $this->objectRepository,
                $this->resolveRevisionService,
                $this->treeToFlatEntriesService,
                $this->createSegmentTreeService,
                $this->saveTreeObjectService,
                $this->createCommitTreeService,
                $this->diffIndexHelper
            );
            $actual = $useCase($request);

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            function () {
                $diffStat = DiffStat::new('file1');
                $diffStat->delete();
                $diffStat->insert();

                return [
                    'message' => 'second commit',
                    'headRef' => Reference::new(ReferenceType::Local, 'main'),
                    'target' => 'file1',
                    'treeEntry' => TreeEntry::new(
                        ObjectType::Blob,
                        GitFileMode::DefaultFile,
                        'file1',
                        ObjectHash::parse('60b27f004e454aca81b0480209cce5081ec52391'),
                    ),
                    'indexEntry' => IndexEntry::new(
                        FileStatFactory::exec(),
                        ObjectHash::parse('60b27f004e454aca81b0480209cce5081ec52390'),
                        TrackedPath::parse('file1')
                    ),
                    'treeContents' => "old file\n",
                    'indexContents' => "new file\n",
                    'diffStat' => $diffStat,
                    'commitHash' => ObjectHash::parse('397c3f85532053c1abdd7d9f9d44f783ef6aef11'),
                    'expectedCommitMessage' => '[main 397c3f8] second commit',
                    'expectedSummary' => ' 1 files changed, 1 insertions(+), 1 deletions(-)',
                    'expectedHistories' => [' mode change 100644 => 100755 file1'],
                ];
            }
        ]);

    it(
        'returns success and outputs commit details, on third commit and delete file',
        function (
            string $message,
            Reference $headRef,
            string $target,
            TreeEntry $treeEntry,
            string $treeContents,
            DiffStat $diffStat,
            ObjectHash $commitHash,
            string $expectedCommitMessage,
            string $expectedSummary,
            array $expectedHistories
        ) {
            $this->input->shouldReceive('getOption')->with('message')->andReturn($message);

            $parentCommitHash = ObjectHashFactory::random();
            $parentCommit = CommitObjectFactory::new();
            $treeObject = TreeObjectFactory::new();
            $segmentTree = SegmentTreeFactory::new();
            $treeHash = ObjectHashFactory::random();
            $commitObject = CommitObjectFactory::new();

            $index = GitIndex::new();
            $treeEntries = HashMap::new();
            $treeEntries->set($treeEntry->objectName, $treeEntry);

            $this->resolveRevisionService->shouldReceive('__invoke')->andReturn($parentCommitHash)->once();
            $this->objectRepository->shouldReceive('getCommit')->withArgs(expectEqualArg($parentCommitHash))->andReturn($parentCommit)->once();
            $this->indexRepository->shouldReceive('getOrCreate')->andReturn($index)->once();
            $this->refRepository
                ->shouldReceive('headType')->andReturn(HeadType::Reference)->once()
                ->shouldReceive('head')->andReturn($headRef)->times(2); // call in currentHeadLabel and updateRef
            $this->objectRepository->shouldReceive('getTree')->withArgs(expectEqualArg($parentCommit->treeHash()))->andReturn($treeObject)->once();
            $this->treeToFlatEntriesService->shouldReceive('__invoke')->withArgs(expectEqualArg($treeObject))->andReturn($treeEntries)->once();

            // diffIndex
            $this->diffIndexHelper
                ->shouldReceive('targetEntry')->withArgs(expectEqualArg(
                    $index->entries,
                    $treeEntries
                ))->andReturn($target)->once()
                ->shouldReceive('getOldStatusFromTree')->withArgs(expectEqualArg($treeEntry))->andReturn([$treeEntry->gitFileMode, $treeEntry->objectHash])->once()
                ->shouldReceive('getNewStatusFromIndex')->with(null)->andReturn([GitFileMode::Unknown, ObjectHash::zero()])->once()
                ->shouldReceive('getOldContentsFromTree')->withArgs(expectEqualArg($treeEntry))->andReturn($treeContents)->once()
                ->shouldReceive('getNewContentsFromIndex')->with(null)->andReturnNull()->once()
                ->shouldReceive('countDiff')->withArgs(expectEqualArg(
                    $treeContents,
                    null,
                    $target
                ))->andReturn($diffStat)->once()
                ->shouldReceive('nextTargetEntry')->withArgs(expectEqualArg(
                    $treeEntry,
                    null,
                    $treeEntries,
                    $index->entries
                ))->andReturnNull()->once()
                ->shouldReceive('isSame')->withArgs(expectEqualArg($treeEntry->gitFileMode, GitFileMode::Unknown, $treeEntry->objectHash, ObjectHash::zero()))->andReturn(false)->once()
                ->shouldReceive('isAdded')->withArgs(expectEqualArg($treeEntry->gitFileMode, $treeEntry->objectHash))->andReturn(false)->once()
                ->shouldReceive('isModefied')->withArgs(expectEqualArg($treeEntry->gitFileMode, $treeEntry->objectHash, GitFileMode::Unknown, ObjectHash::zero()))->andReturn(false)->once()
                ->shouldReceive('isDeleted')->withArgs(expectEqualArg(GitFileMode::Unknown, ObjectHash::zero()))->andReturn(true)->once();

            $this->createSegmentTreeService->shouldReceive('__invoke')->withArgs(expectEqualArg($index))->andReturn($segmentTree)->once();
            $this->saveTreeObjectService->shouldReceive('__invoke')->withArgs(expectEqualArg($segmentTree))->andReturn($treeHash)->once();
            $this->createCommitTreeService->shouldReceive('__invoke')->withArgs(expectEqualArg($treeHash, $message, $parentCommitHash))->andReturn($commitObject)->once();
            $this->objectRepository->shouldReceive('save')->withArgs(expectEqualArg($commitObject))->andReturn($commitHash)->once();
            $this->refRepository->shouldReceive('createOrUpdate')->withArgs(expectEqualArg($headRef, $commitHash))->once();
            $this->printer
                ->shouldReceive('writeln')->with($expectedCommitMessage)->once()
                ->shouldReceive('writeln')->with($expectedSummary)->once()
                ->shouldReceive('writeln')->withArgs(expectEqualArg($expectedHistories))->once();

            $request = CommitRequest::new($this->input);
            $useCase = new CommitUseCase(
                $this->printer,
                $this->indexRepository,
                $this->refRepository,
                $this->objectRepository,
                $this->resolveRevisionService,
                $this->treeToFlatEntriesService,
                $this->createSegmentTreeService,
                $this->saveTreeObjectService,
                $this->createCommitTreeService,
                $this->diffIndexHelper
            );
            $actual = $useCase($request);

            expect($actual)->toBe(Result::Success);
        }
    )
        ->with([
            function () {
                $diffStat = DiffStat::new('file1');
                $diffStat->delete();
                $diffStat->insert();

                return [
                    'message' => 'second commit',
                    'headRef' => Reference::new(ReferenceType::Local, 'main'),
                    'target' => 'file1',
                    'treeEntry' => TreeEntry::new(
                        ObjectType::Blob,
                        GitFileMode::DefaultFile,
                        'file1',
                        ObjectHash::parse('60b27f004e454aca81b0480209cce5081ec52391'),
                    ),
                    'treeContents' => "old file\n",
                    'diffStat' => $diffStat,
                    'commitHash' => ObjectHash::parse('397c3f85532053c1abdd7d9f9d44f783ef6aef11'),
                    'expectedCommitMessage' => '[main 397c3f8] second commit',
                    'expectedSummary' => ' 1 files changed, 1 insertions(+), 1 deletions(-)',
                    'expectedHistories' => [' delete mode 100644 file1'],
                ];
            }
        ]);

    it(
        'returns git error and outputs fatal message on throws an exception no file change',
        function () {
            $this->input->shouldReceive('getOption')->with('message')->andReturn('dummy message');
            $this->resolveRevisionService->shouldReceive('__invoke')->andReturnNull()->once();
            $this->indexRepository->shouldReceive('getOrCreate')->andReturn(GitIndexFactory::new())->once();
            $this->refRepository
                ->shouldReceive('headType')->andReturn(HeadType::Reference)->once()
                ->shouldReceive('head')->andReturn(Reference::new(ReferenceType::Local, 'main'));
            $this->diffIndexHelper->shouldReceive('targetEntry')->withArgs(expectEqualArg([], HashMap::new()))->andReturnNull()->once();
            $this->printer->shouldReceive('writeln')->with("On branch main\nnothing to commit, working tree clean")->once();

            $request = CommitRequest::new($this->input);
            $useCase = new CommitUseCase(
                $this->printer,
                $this->indexRepository,
                $this->refRepository,
                $this->objectRepository,
                $this->resolveRevisionService,
                $this->treeToFlatEntriesService,
                $this->createSegmentTreeService,
                $this->saveTreeObjectService,
                $this->createCommitTreeService,
                $this->diffIndexHelper
            );

            $actual = $useCase($request);

            expect($actual)->toBe(Result::GitError);
        }
    );

    it(
        'returns internal error and outputs stack trace on throws an exception head-type is unknown',
        function () {
            $this->input->shouldReceive('getOption')->with('message')->andReturn('dummy message');
            $this->resolveRevisionService->shouldReceive('__invoke')->andReturnNull()->once();
            $this->indexRepository->shouldReceive('getOrCreate')->andReturn(GitIndexFactory::new())->once();
            $this->refRepository->shouldReceive('headType')->andReturn(HeadType::Unknown)->once();
            $this->printer->shouldReceive('stackTrace')->withArgs(expectEqualArg(new LogicException('This branch is not reached')))->once();

            $request = CommitRequest::new($this->input);
            $useCase = new CommitUseCase(
                $this->printer,
                $this->indexRepository,
                $this->refRepository,
                $this->objectRepository,
                $this->resolveRevisionService,
                $this->treeToFlatEntriesService,
                $this->createSegmentTreeService,
                $this->saveTreeObjectService,
                $this->createCommitTreeService,
                $this->diffIndexHelper
            );

            $actual = $useCase($request);

            expect($actual)->toBe(Result::InternalError);
        }
    );
});
