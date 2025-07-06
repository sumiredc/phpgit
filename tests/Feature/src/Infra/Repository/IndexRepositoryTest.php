<?php

declare(strict_types=1);

use Phpgit\Domain\GitIndex;
use Phpgit\Domain\IndexEntry;
use Phpgit\Domain\ObjectHash;
use Phpgit\Domain\TrackedPath;
use Phpgit\Infra\Repository\IndexRepository;
use Tests\CommandRunner;
use Tests\Factory\FileStatFactory;
use Tests\Factory\GitIndexFactory;
use Tests\Factory\IndexEntryFactory;
use Tests\Factory\ObjectHashFactory;
use Tests\Factory\TrackedPathFactory;

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

describe('save', function () {
    it(
        'saves index',
        function () {
            $repository = new IndexRepository;
            $index = GitIndex::new();

            expect($repository->exists())->toBeFalse();

            $repository->save($index);

            expect($repository->exists())->toBeTrue();
        }
    );

    it(
        'throws an exception on fails to file_put_contents',
        function () {
            $repository = new IndexRepository;
            $index = GitIndex::new();

            unlink(F_GIT_INDEX);
            mkdir(F_GIT_INDEX);

            expect(fn() => $repository->save($index))->toThrow(new RuntimeException('failed to save Git Index'));

            rmdir(F_GIT_INDEX);
        }
    );
});

describe('create', function () {
    it(
        'returns index on create index',
        function () {
            $repository = new IndexRepository;

            $actual = $repository->create();

            expect($actual)->toEqual(GitIndex::new());
            expect($repository->exists())->toBeTrue();
        }
    );

    it(
        'throws an exception on exists index',
        function () {
            $repository = new IndexRepository;

            CommandRunner::run('rm -rf /tmp/project/.git');

            expect(fn() => $repository->create())->toThrow(new RuntimeException('failed to create index'));

            CommandRunner::run('php git init'); // recovery git dir
        }
    );
});

describe('getOrCreate', function () {
    it(
        'returns creating index',
        function () {
            $repository = new IndexRepository;

            unlink(F_GIT_INDEX);

            expect($repository->exists())->toBeFalse();

            $actual = $repository->getOrCreate();

            expect($actual)->toEqual(GitIndex::new());
        }
    );

    it(
        'returns index on exists index',
        function () {
            $repository = new IndexRepository;

            $index = GitIndex::new();
            $index->addEntry(IndexEntryFactory::new());

            $repository->save($index);
            $actual = $repository->getOrCreate();

            expect($actual)->toEqual($index);
        }
    );
});

describe('get', function () {
    it(
        'returns parsed index',
        function () {
            $repository = new IndexRepository;

            $index = GitIndex::new();
            $index->addEntry(IndexEntry::new(
                FileStatFactory::new(),
                ObjectHashFactory::new(),
                TrackedPath::parse('1'),
            ));
            $index->addEntry(IndexEntry::new(
                FileStatFactory::new(),
                ObjectHashFactory::new(),
                TrackedPath::parse('22'),
            ));
            $index->addEntry(IndexEntry::new(
                FileStatFactory::new(),
                ObjectHashFactory::new(),
                TrackedPath::parse('333'),
            ));
            $index->addEntry(IndexEntry::new(
                FileStatFactory::new(),
                ObjectHashFactory::new(),
                TrackedPath::parse('4444'),
            ));
            $index->addEntry(IndexEntry::new(
                FileStatFactory::new(),
                ObjectHashFactory::new(),
                TrackedPath::parse('55555'),
            ));
            $index->addEntry(IndexEntry::new(
                FileStatFactory::new(),
                ObjectHashFactory::new(),
                TrackedPath::parse('666666'),
            ));
            $index->addEntry(IndexEntry::new(
                FileStatFactory::new(),
                ObjectHashFactory::new(),
                TrackedPath::parse('7777777'),
            ));
            $index->addEntry(IndexEntry::new(
                FileStatFactory::new(),
                ObjectHashFactory::new(),
                TrackedPath::parse('88888888'),
            ));

            $repository->save($index);

            $actual = $repository->get();

            expect($actual)->toEqual($index);
        }
    );

    it(
        'throws an exception on fails fopen git index',
        function () {
            $repository = new IndexRepository;

            unlink(F_GIT_INDEX);
            rmdir(F_GIT_INDEX);

            expect(fn() => $repository->get())->toThrow(new RuntimeException('failed to fopen Git Index'));
        }
    );

    it(
        'throws an exception on fails fread git index',
        function () {
            $repository = new IndexRepository;

            unlink(F_GIT_INDEX);
            mkdir(F_GIT_INDEX);

            expect(fn() => $repository->get())->toThrow(new RuntimeException('failed to fread Git Index header'));

            rmdir(F_GIT_INDEX);
        }
    );
});
