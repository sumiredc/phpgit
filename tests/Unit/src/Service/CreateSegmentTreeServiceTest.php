<?php

declare(strict_types=1);

use Phpgit\Domain\FileStat;
use Phpgit\Domain\GitIndex;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\SegmentTree;
use Phpgit\Domain\TrackedPath;
use Phpgit\Exception\InvalidObjectException;
use Phpgit\Service\CreateSegmentTreeService;
use Tests\Factory\FileStatFactory;
use Tests\Factory\ObjectHashFactory;
use Tests\Factory\SegmentTreeFactory;

beforeEach(function () {
    $this->objectRepository = Mockery::mock(ObjectRepositoryInterface::class);
});

describe('__invoke', function () {
    it(
        'should match to segmentTree',
        function (array $entries, SegmentTree $expected) {
            $index = GitIndex::new();
            foreach ($entries as $entry) {
                $index->addEntry($entry);
            }

            $this->objectRepository->shouldReceive('exists')->andReturn(true)->times(count($entries));

            $service = new CreateSegmentTreeService($this->objectRepository);
            $actual = $service($index);

            expect($actual)->toEqual($expected);
        }
    )
        ->with([
            'no entries' => [
                'entries' => [],
                'expected' => SegmentTree::new(),
            ],
            'exists entries' => [
                'entries' => array_map(fn(string $file) => IndexEntry::new(
                    FileStatFactory::new(),
                    ObjectHashFactory::new(),
                    TrackedPath::parse($file),
                ), [
                    'README.md',
                    'CONTRIBUTING.md',
                    'html/public/index.html',
                    'html/public/style.css',
                    'html/errors/not-found.html',
                    'html/errors/internal-server-error.html',
                    'html/errors/forbidden.html',
                ]),
                'expected' => SegmentTreeFactory::fromArray([
                    'CONTRIBUTING.md' => IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHashFactory::new(),
                        TrackedPath::parse('CONTRIBUTING.md'),
                    ),
                    'README.md' => IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHashFactory::new(),
                        TrackedPath::parse('README.md'),
                    ),
                    'html' => [
                        'errors' => [
                            'forbidden.html' => IndexEntry::new(
                                FileStatFactory::new(),
                                ObjectHashFactory::new(),
                                TrackedPath::parse('html/errors/forbidden.html'),
                            ),
                            'internal-server-error.html' => IndexEntry::new(
                                FileStatFactory::new(),
                                ObjectHashFactory::new(),
                                TrackedPath::parse('html/errors/internal-server-error.html')
                            ),
                            'not-found.html' => IndexEntry::new(
                                FileStatFactory::new(),
                                ObjectHashFactory::new(),
                                TrackedPath::parse('html/errors/not-found.html')
                            ),
                        ],
                        'public' => [
                            'index.html' => IndexEntry::new(
                                FileStatFactory::new(),
                                ObjectHashFactory::new(),
                                TrackedPath::parse('html/public/index.html')
                            ),
                            'style.css' => IndexEntry::new(
                                FileStatFactory::new(),
                                ObjectHashFactory::new(),
                                TrackedPath::parse('html/public/style.css')
                            ),
                        ],
                    ]
                ]),
            ]
        ]);

    it(
        'fails to throws InvalidObjectException when does not exist object',
        function (array $entries, Throwable $expected) {
            $index = GitIndex::new();
            foreach ($entries as $entry) {
                $index->addEntry($entry);
            }

            $this->objectRepository->shouldReceive('exists')->andReturn(false);

            $service = new CreateSegmentTreeService($this->objectRepository);

            expect(fn() => $service($index))->toThrow($expected);
        }
    )
        ->with([
            [
                array_map(fn(string $file) => IndexEntry::new(
                    FileStat::newForCacheinfo(33188),
                    ObjectHash::parse('829c3804401b0727f70f73d4415e162400cbe57b'),
                    TrackedPath::parse($file),
                ), [
                    'README.md',
                    'CONTRIBUTING.md',
                    'html/public/index.html',
                    'html/public/style.css',
                    'html/errors/not-found.html',
                    'html/errors/internal-server-error.html',
                    'html/errors/forbidden.html',
                ]),
                new InvalidObjectException(
                    'error: invalid object 100644 829c3804401b0727f70f73d4415e162400cbe57b for \'CONTRIBUTING.md\''
                )
            ]
        ]);
});
