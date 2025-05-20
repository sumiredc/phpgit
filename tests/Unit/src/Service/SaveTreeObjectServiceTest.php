<?php

declare(strict_types=1);

use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\Repository\ObjectRepositoryInterface;
use Phpgit\Domain\SegmentTree;
use Phpgit\Domain\TrackingFile;
use Phpgit\Service\SaveTreeObjectService;
use Tests\Factory\FileStatFactory;
use Tests\Factory\SegmentTreeFactory;

beforeEach(function () {
    $this->objectRepository = Mockery::mock(ObjectRepositoryInterface::class);
});

describe('__invoke', function () {
    it(
        'should match object hash',
        function (
            SegmentTree $segmentTree,
            array $treeHashs,
            int $getTimes,
            int $saveTimes,
            string $expected
        ) {
            $this->objectRepository->shouldReceive('save')->andReturn(...$treeHashs)->times($saveTimes);

            $service = new SaveTreeObjectService($this->objectRepository);
            $actual = $service($segmentTree);

            expect($actual->value)->toBe($expected);
        }
    )
        ->with([
            [
                'segmentTree' => SegmentTreeFactory::fromArray([
                    'README.md' => IndexEntry::new(
                        FileStatFactory::new(),
                        ObjectHash::parse('8ec9a00bfd09b3190ac6b22251dbb1aa95a0579d'),
                        TrackingFile::new('README.md'),
                    ),
                    'html' => [
                        'errors' => [
                            'forbidden.html' => IndexEntry::new(
                                FileStatFactory::new(),
                                ObjectHash::parse('9f13bdd41b1e8ebebc40e061aaa7204a1d87df79'),
                                TrackingFile::new('html/errors/forbidden.html'),
                            ),
                        ],
                        'public' => [
                            'index.html' => IndexEntry::new(
                                FileStatFactory::new(),
                                ObjectHash::parse('f6013a00b362253c64368d6eebc50ea2131754e2'),
                                TrackingFile::new('html/public/index.html')
                            ),
                        ],
                    ]
                ]),
                'treeHashs' => [
                    ObjectHash::parse('61c9b2b17db77a27841bbeeabff923448b0f6388'), // public
                    ObjectHash::parse('570043596e41f9067d43fbff99f1acb348a090bf'), // errors
                    ObjectHash::parse('950a39b6c2934bb72f2def76c71e88e9c035385f'), // html
                    ObjectHash::parse('5dcbdf371f181b9b7a41a4be7be70f8cbee67da7'), // root tree
                ],
                'getTimes' => 3,
                'saveTimes' => 4,
                'expected' => '5dcbdf371f181b9b7a41a4be7be70f8cbee67da7',
            ]
        ]);
});
