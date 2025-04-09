<?php

require __DIR__ . '/../vendor/autoload.php';

use Phpgit\Command\GitInitCommand;
use Symfony\Component\Console\Application;

$application = new Application('phpgit', '0.1.0');

$application->add(new GitInitCommand());

$application->run();
