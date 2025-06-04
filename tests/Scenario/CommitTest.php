<?php

use Tests\CommandRunner;

beforeEach(function () {
    refreshGit();
});

test('use Low-level Commands', function () {
    $res = CommandRunner::run('php git init');
    expect($res->exitCode)->toBeExitSuccess();

    CommandRunner::run('echo "Hello, World!" > /tmp/project/hello.txt');
    $res = CommandRunner::run('php git hash-object hello.txt');
    expect($res->exitCode)->toBeExitSuccess();
    expect($res->output)->toBe('8ab686eafeb1f44702738c8b0f24f2567c36da6d');

    $res = CommandRunner::run('php git update-index --add hello.txt');
    expect($res->exitCode)->toBeExitSuccess();

    $res = CommandRunner::run('php git write-tree');
    expect($res->exitCode)->toBeExitSuccess();
    $treeHash = $res->output;

    $res = CommandRunner::run("php git commit-tree -m \\\"first commit\\\" {$treeHash}");
    expect($res->exitCode)->toBeExitSuccess();
    $commitHash = $res->output;

    $res = CommandRunner::run("php git update-ref HEAD {$commitHash}");
    expect($res->exitCode)->toBeExitSuccess();

    $res = CommandRunner::run("php git rev-parse HEAD");
    expect($res->exitCode)->toBeExitSuccess();
    expect($res->output)->toBe($commitHash);

    CommandRunner::run('echo "Updated" >> /tmp/project/hello.txt');
    $res = CommandRunner::run('php git hash-object hello.txt');
    expect($res->exitCode)->toBeExitSuccess();
    expect($res->output)->toBe('bc23f20cf0a11b5455ec5d617e1879ca804c329f');

    $res = CommandRunner::run("php git update-index --add hello.txt");
    expect($res->exitCode)->toBeExitSuccess();

    $res = CommandRunner::run('php git write-tree');
    expect($res->exitCode)->toBeExitSuccess();
    $treeHash = $res->output;

    $res = CommandRunner::run('php git rev-parse HEAD');
    expect($res->exitCode)->toBeExitSuccess();
    expect($res->output)->toBe($commitHash);

    $parentHash = $res->output;
    $res = CommandRunner::run("php git commit-tree {$treeHash} -m \\\"Update hello.txt\\\" -p {$parentHash}");
    expect($res->exitCode)->toBeExitSuccess();
    $commitHash = $res->output;

    $res = CommandRunner::run("php git update-ref HEAD {$commitHash}");
    expect($res->exitCode)->toBeExitSuccess();

    $res = CommandRunner::run("php git rev-parse HEAD");
    expect($res->exitCode)->toBeExitSuccess();
    expect($res->output)->toBe($commitHash);
});

test('use Main Porcelain Commands', function () {
    $res = CommandRunner::run('php git init');
    expect($res->exitCode)->toBeExitSuccess();

    CommandRunner::run('echo "Hello, World!" > /tmp/project/hello1.txt');
    CommandRunner::run('echo "Hello, World!!" > /tmp/project/hello2.txt');
    CommandRunner::run('echo "Hello, World!!!" > /tmp/project/hello3.txt');

    $res = CommandRunner::run('php git add -A');
    expect($res->exitCode)->toBeExitSuccess();
});
