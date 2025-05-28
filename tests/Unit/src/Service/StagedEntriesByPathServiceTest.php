<?php

declare(strict_types=1);

use Phpgit\Domain\GitIndex;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\PathType;
use Phpgit\Domain\TrackedPath;
use Phpgit\Service\StagedEntriesByPathService;
use Tests\Factory\FileStatFactory;
use Tests\Factory\ObjectHashFactory;

beforeEach(function () {
    $this->gitIndex = GitIndex::new();

    foreach (
        [
            'src/main.go',
            'src/go.mod',
            'src/domain/employee.go',
            'src/domain/employee_test.go',
            'src/domain/user.go',
            'src/domain/user_test.go',
            'trashed/company.go',
            'trashed/company_test.go',
            'README.md',
            'CONTRIBUTING.md',
        ] as $path
    ) {
        $entry = IndexEntry::new(
            FileStatFactory::new(),
            ObjectHashFactory::new(),
            TrackedPath::parse($path),
        );

        $this->gitIndex->addEntry($entry);
    }
});

describe('__invoke', function () {
    it(
        'returns to matched entries',
        function (string $path, PathType $pathType, array $matchedPaths) {
            $service = new StagedEntriesByPathService();

            $actual = $service($this->gitIndex, TrackedPath::parse($path), $pathType);

            foreach ($matchedPaths as $expected) {
                expect($actual->exists($expected))->toBeTrue();
            }
        }
    )
        ->with([
            'path type is file' => [
                'README.md',
                PathType::File,
                [
                    'README.md'
                ]
            ],
            'path type is directory' => [
                'src',
                PathType::Directory,
                [
                    'src/domain/employee.go',
                    'src/domain/employee_test.go',
                    'src/domain/user.go',
                    'src/domain/user_test.go',
                ]
            ],
            'path type is pattern' => [
                'src/domain/*_test.go',
                PathType::Pattern,
                [
                    'src/domain/employee_test.go',
                    'src/domain/user_test.go',
                ]
            ],
            'path type is unknown' => [
                'trashed',
                PathType::Unknown,
                [
                    'trashed/company.go',
                    'trashed/company_test.go',
                ]
            ],
        ]);
});
