<?php

declare(strict_types=1);

use Phpgit\Request\GitCommitTreeRequest;
use Symfony\Component\Console\Input\InputInterface;

beforeEach(function () {
    $this->input = Mockery::mock(InputInterface::class);
});

describe('new', function () {
    it(
        'match to args to properties',
        function (string $tree, string $message) {
            $this->input->shouldReceive('getArgument')->with('tree')->andReturn($tree)->once();
            $this->input->shouldReceive('getOption')->with('message')->andReturn($message)->once();

            $actual = GitCommitTreeRequest::new($this->input);

            expect($actual->tree)->toBe($tree);
            expect($actual->message)->toBe($message);
        }
    )
        ->with([
            [
                '081b3bbbc244693f20cf87f9de45db666faa4dc8',
                'first commit',
            ],
            [
                'fc01489d8afd08431c7245b4216ea9d01856c3b9',
                'second commit',
            ],
            [
                'f8933dba7b7326ee773408142b906c47fa336f9f',
                'third commit',
            ]
        ]);
});
