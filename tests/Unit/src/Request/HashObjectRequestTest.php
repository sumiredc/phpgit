<?php

declare(strict_types=1);

use Phpgit\Command\CommandInterface;
use Phpgit\Request\HashObjectRequest;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

beforeEach(function () {
    $this->command = Mockery::mock(CommandInterface::class);
    $this->input = Mockery::mock(InputInterface::class);
});


describe('setUp', function () {
    it(
        'calls setup args function',
        function () {
            $this->command
                ->shouldReceive('addArgument')
                ->with('file', InputArgument::REQUIRED, 'The file to hash')
                ->once();

            HashObjectRequest::setUp($this->command);

            $refClass = new ReflectionClass(HashObjectRequest::class);
            $assertNew = $refClass->getMethod('assertNew');
            $assertNew->invoke($refClass);

            expect(true)->toBeTrue();
        }
    );
});

describe('new', function () {
    beforeEach(function () {
        $refClass = new ReflectionClass(HashObjectRequest::class);
        $unlock = $refClass->getMethod('unlock');
        $unlock->invoke($refClass);
    });

    it(
        'match to args to properties',
        function (string $file) {
            $this->input->shouldReceive('getArgument')->with('file')->andReturn($file)->once();

            $actual = HashObjectRequest::new($this->input);

            expect($actual->file)->toBe($file);
        }
    )
        ->with([
            ['README.md'],
            ['src/app.php'],
            ['app/Controllers/Controller.php']
        ]);
});
