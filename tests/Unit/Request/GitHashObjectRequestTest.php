<?php

declare(strict_types=1);

use Phpgit\Request\GitHashObjectRequest;
use Symfony\Component\Console\Input\InputInterface;

beforeEach(function () {
    $this->input = Mockery::mock(InputInterface::class);
});

describe('new', function () {
    it(
        'match to args to properties',
        function (string $file) {
            $this->input->shouldReceive('getArgument')->with('file')->andReturn($file)->once();

            $actual = GitHashObjectRequest::new($this->input);

            expect($actual->file)->toBe($file);
        }
    )
        ->with([
            ['README.md'],
            ['src/app.php'],
            ['app/Controllers/Controller.php']
        ]);
});
