<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Statamic\Facades\Fieldset;
use Tests\TestCase;

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

pest()->extend(TestCase::class)
    ->beforeEach(function (): void {
        Str::createRandomStringsNormally();
        Str::createUuidsNormally();
        Http::preventStrayRequests();
        Process::preventStrayProcesses();
        Sleep::fake();
    })
    ->in('Browser', 'Feature', 'Unit');

// Console tests mutate the shared content/ tree, so they run in their own
// serial pass (see composer.json test:unit) instead of the parallel one.
pest()->group('console')->in('Feature/Console');

pest()->tia()->locally()->filtered();

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

expect()->extend('toBeOne', fn () => $this->toBe(1));

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
 * Scratch directory for scaffold test artefacts.
 */
function bedrockTestScratchPath(): string
{
    return storage_path('framework/testing/bedrock');
}

/**
 * Fieldsets directory the Statamic fieldset repository points at during
 * scaffold tests.
 */
function bedrockTestFieldsetsPath(): string
{
    return bedrockTestScratchPath().'/fieldsets';
}

/**
 * Provision an isolated scaffold workspace (fieldsets + views), point the
 * Statamic fieldset repository at it, and rebind the statamic.bedrock.scaffold.*
 * config so the commands under test write into it. The real fieldsets are
 * copied in as seeds so fieldset imports keep resolving.
 */
function setUpBedrockScaffoldPaths(): void
{
    $scratch = bedrockTestScratchPath();
    $fieldsets = bedrockTestFieldsetsPath();
    $blocksViews = "{$scratch}/views/blocks";
    $setsViews = "{$scratch}/views/sets";

    foreach ([$fieldsets, $blocksViews, $setsViews] as $dir) {
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    File::copyDirectory(resource_path('fieldsets'), $fieldsets);

    Fieldset::setDirectory($fieldsets);

    config([
        'statamic.bedrock.scaffold.blocks_views_path' => $blocksViews,
        'statamic.bedrock.scaffold.sets_views_path' => $setsViews,
    ]);
}

function tearDownBedrockScaffoldPaths(): void
{
    $scratch = bedrockTestScratchPath();
    if (is_dir($scratch)) {
        File::deleteDirectory($scratch);
    }
}

/**
 * Build a unique entry id/slug. Use the returned string for both `->id()` and
 * `->slug()` on the test entry so Statamic's slug-derived filename matches the
 * afterEach cleanup glob.
 */
function bedrockTestEntryId(string $prefix): string
{
    return $prefix.'-'.Str::random(6);
}
