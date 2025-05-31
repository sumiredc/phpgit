<?php

declare(strict_types=1);

if (!file_exists('/.dockerenv')) {
    exit("❌ Tests must be run inside Docker.\n");
}

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeExitSuccess', fn() => $this->toBe(0));
expect()->extend('toBeExitFailure', fn() => $this->toBe(1));
expect()->extend('toBeExitInvalid', fn() => $this->toBe(2));
expect()->extend('toBeExitInternalError', fn() => $this->toBe(70));
expect()->extend('toBeExitGitError', fn() => $this->toBe(128));

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * This function is giving to "withArgs" method arg in Mockery
 * 
 * ex: 
 *  $mock = Mockery::mock(Someting::class);
 *  $mock->shouldReceive('someting')->withArgs(expectEqualArg($expected));
 */
function expectEqualArg(mixed ...$expected): callable
{
    return function (mixed ...$actual) use ($expected): bool {
        foreach ($expected as $i => $expectedValue) {
            expect($actual[$i])->toEqual($expectedValue);
        }

        return true;
    };
}

function refreshGit(): void
{
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(F_GIT_DIR, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $file) {
        $file->isDir() ? rmdir($file) : unlink($file);
    }

    rmdir(F_GIT_DIR);
}
