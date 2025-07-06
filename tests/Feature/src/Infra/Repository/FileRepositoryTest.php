<?php

declare(strict_types=1);

use Phpgit\Domain\FileStat;
use Phpgit\Domain\HashMap;
use Phpgit\Domain\PathType;
use Phpgit\Domain\TrackedPath;
use Phpgit\Infra\Repository\FileRepository;
use Tests\CommandRunner;

beforeAll(function () {
    CommandRunner::run('php git init');
});

beforeEach(function () {
    refreshObjects();
    set_error_handler(fn() => true);
});

afterEach(function () {
    restore_error_handler();
});


describe('exists', function () {
    it(
        'returns true on exists file',
        function () {
            $repository = new FileRepository();
            $trackedPath = TrackedPath::parse('exists-path');
            file_put_contents($trackedPath->full(), 'sample');

            expect($repository->exists($trackedPath))->toBeTrue();
        }
    );

    it(
        'returns false on does not exists file',
        function () {
            $repository = new FileRepository();
            $trackedPath = TrackedPath::parse('not-exists-path');

            expect($repository->exists($trackedPath))->toBeFalse();
        }
    );
});

describe('existsDir', function () {
    it(
        'returns true on exists dir',
        function () {
            $repository = new FileRepository();
            $trackedPath = TrackedPath::parse('exists-dir');
            mkdir($trackedPath->full());

            expect($repository->existsDir($trackedPath))->toBeTrue();
        }
    );

    it(
        'returns false on does not exists dir',
        function () {
            $repository = new FileRepository();
            $trackedPath = TrackedPath::parse('not-exists-dir');

            expect($repository->existsDir($trackedPath))->toBeFalse();
        }
    );
});

describe('getContents', function () {
    it(
        'returns contents on exists file',
        function () {
            $contents = 'dummy contents';

            $repository = new FileRepository();
            $trackedPath = TrackedPath::parse('exists-file');
            file_put_contents($trackedPath->full(), $contents);

            expect($repository->getContents($trackedPath))->toBe($contents);
        }
    );

    it(
        'throws an exception on does not exists file',
        function () {
            $repository = new FileRepository();
            $trackedPath = TrackedPath::parse('not-exists-file');

            expect(fn() => $repository->getContents($trackedPath))
                ->toThrow(new RuntimeException('failed to get contents: /tmp/project/not-exists-file'));
        }
    );
});

describe('getStat', function () {
    it(
        'returns file stat on exists file',
        function () {
            $contents = 'dummy contents';

            $repository = new FileRepository();
            $trackedPath = TrackedPath::parse('exists-file');
            file_put_contents($trackedPath->full(), $contents);
            $expected = FileStat::new(stat($trackedPath->full()));

            expect($repository->getStat($trackedPath))->toEqual($expected);
        }
    );

    it(
        'throws an exception on does not exists file',
        function () {
            $repository = new FileRepository();
            $trackedPath = TrackedPath::parse('not-exists-file');

            expect(fn() => $repository->getStat($trackedPath))
                ->toThrow(new RuntimeException('failed to get stat: /tmp/project/not-exists-file'));
        }
    );
});

describe('search', function () {
    it(
        'returns HashMap on PathType is File',
        function () {
            $repository = new FileRepository();
            $trackedPath = TrackedPath::parse('sample');
            $expected = HashMap::parse([
                $trackedPath->value => $trackedPath
            ]);

            $actual = $repository->search($trackedPath, PathType::File);

            expect($actual)->toEqual($expected);
        }
    );

    it(
        'returns HashMap on PathType is Directory',
        function () {
            $repository = new FileRepository();
            $trackedPath = TrackedPath::parse('dir-a');

            mkdir('/tmp/project/dir-a');
            touch('/tmp/project/dir-a/a.txt');
            touch('/tmp/project/dir-a/b.txt');
            touch('/tmp/project/dir-a/c.txt');

            mkdir('/tmp/project/dir-a/dir-b');
            touch('/tmp/project/dir-a/dir-b/a.txt');
            touch('/tmp/project/dir-a/dir-b/b.txt');

            mkdir('/tmp/project/link-target');
            symlink('/tmp/project/link-target', '/tmp/project/dir-a/d-link'); // ignore link

            mkdir('/tmp/project/dir-a/.git'); // ignore dir

            $expected = HashMap::parse([
                'dir-a/a.txt' => TrackedPath::parse('dir-a/a.txt'),
                'dir-a/b.txt' => TrackedPath::parse('dir-a/b.txt'),
                'dir-a/c.txt' => TrackedPath::parse('dir-a/c.txt'),
                'dir-a/dir-b/a.txt' => TrackedPath::parse('dir-a/dir-b/a.txt'),
                'dir-a/dir-b/b.txt' => TrackedPath::parse('dir-a/dir-b/b.txt'),
            ]);

            $actual = $repository->search($trackedPath, PathType::Directory);

            expect($actual)->toEqual($expected);
        }
    );

    it(
        'returns HashMap on PathType is Pattern',
        function () {
            $repository = new FileRepository();
            $trackedPath = TrackedPath::parse('dir-b/*');

            mkdir('/tmp/project/dir-b');
            touch('/tmp/project/dir-b/a.txt');
            touch('/tmp/project/dir-b/b.txt');
            touch('/tmp/project/dir-b/c.txt');

            $expected = HashMap::parse([
                'dir-b/a.txt' => TrackedPath::parse('dir-b/a.txt'),
                'dir-b/b.txt' => TrackedPath::parse('dir-b/b.txt'),
                'dir-b/c.txt' => TrackedPath::parse('dir-b/c.txt'),
            ]);

            $actual = $repository->search($trackedPath, PathType::Pattern);

            expect($actual)->toEqual($expected);
        }
    );

    it(
        'returns empty HashMap on PathType is Unknown',
        function () {
            $repository = new FileRepository();
            $trackedPath = TrackedPath::parse('unknown');
            $actual = $repository->search($trackedPath, PathType::Unknown);

            expect($actual)->toEqual(HashMap::new());
        }
    );
});
