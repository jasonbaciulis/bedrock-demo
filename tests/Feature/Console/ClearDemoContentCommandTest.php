<?php

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\Asset;
use Statamic\Facades\Entry as EntryFacade;
use Statamic\Facades\GlobalVariables;
use Statamic\Facades\Nav;
use Statamic\Facades\Site;
use Statamic\Facades\Stache;
use Statamic\Facades\Term;
use Statamic\Stache\Stores\Store;

beforeEach(function (): void {
    $this->realContentPath = base_path('content');
    $this->realAssetsPath = public_path('assets');
    $this->scratchPath = bedrockTestScratchPath();
    $this->contentPath = $this->scratchPath.'/content-root/content';
    $this->assetsPath = $this->scratchPath.'/content-root/public/assets';

    File::copyDirectory($this->realContentPath, $this->contentPath);
    File::ensureDirectoryExists($this->assetsPath);
    File::put($this->assetsPath.'/.gitkeep', '');

    pointStatamicAtScratchContent($this->contentPath, $this->assetsPath);

    // The command deletes content, so prove the repoint took effect before it runs.
    expect(Stache::store('entries')->directory())->not->toStartWith($this->realContentPath);
});

afterEach(function (): void {
    File::deleteDirectory($this->scratchPath);
});

/**
 * Repoint every Stache store under content/ and the assets disk at a scratch
 * copy, so bedrock:clear deletes copies instead of the real demo content.
 */
function pointStatamicAtScratchContent(string $contentPath, string $assetsPath): void
{
    $realContentPath = base_path('content');

    Stache::stores()
        ->filter(fn (Store $store): bool => Str::startsWith($store->directory(), $realContentPath))
        ->each(fn (Store $store) => $store->directory(
            Str::replaceStart($realContentPath, $contentPath, $store->directory())
        ));

    config(['filesystems.disks.assets.root' => $assetsPath]);

    // The suite shares the real file cache, so give the Stache its own store.
    // Otherwise the emptied scratch index persists into the next test and into
    // the developer's site.
    config(['statamic.stache.cache_store' => 'array']);

    Stache::clear();
}

/**
 * @return list<string>
 */
function clearedGlobalSetHandles(): array
{
    return ['banner', 'browser_appearance', 'newsletter', 'social_media', 'theme'];
}

/**
 * Put values on the home entry in the fields the command strips, so the test
 * proves removal instead of asserting on already-empty fields.
 */
function seedHomeEntryFields(): Entry
{
    $home = EntryFacade::query()
        ->where('collection', 'pages')
        ->where('slug', 'home')
        ->first();

    $home
        ->set('blocks', [['type' => 'test_block']])
        ->set('seo_title', 'Test title')
        ->set('seo_description', 'Test description')
        ->set('og_image', 'og.jpg')
        ->set('twitter_image', 'twitter.jpg')
        ->save();

    return $home;
}

/**
 * @return array<string, string>
 */
function seedDemoAssets(string $assetsPath): array
{
    $paths = [
        'image' => 'images/test-demo-image.txt',
        'avatar' => 'avatars/test-demo-avatar.txt',
        'logo' => 'logos/test-demo-logo.txt',
    ];

    return collect($paths)->map(function (string $path) use ($assetsPath): string {
        File::ensureDirectoryExists(dirname("{$assetsPath}/{$path}"));
        File::put("{$assetsPath}/{$path}", 'test content');

        $asset = Asset::make()->container('assets')->path($path)->syncOriginal();
        $asset->save();

        return $asset->id();
    })->all();
}

function countFilesIn(string $path): int
{
    return count(File::allFiles($path));
}

/**
 * Assert the copied demo content holds everything the command is meant to
 * clear, then seed the home entry fields. A missing fixture fails here instead
 * of turning a later assertion into a false pass.
 */
function seededDemoState(string $assetsPath): Entry
{
    expect(EntryFacade::all()->count())->toBeGreaterThan(1)
        ->and(Term::whereTaxonomy('categories')->count())->toBeGreaterThan(0);

    foreach (clearedGlobalSetHandles() as $handle) {
        expect(GlobalVariables::whereSet($handle)->count())->toBeGreaterThan(0);
    }

    expect(Nav::all()->flatMap(
        fn ($nav) => Site::all()->map->handle()->map(fn (string $siteHandle) => $nav->in($siteHandle)?->tree() ?? [])
    )->flatten()->count())->toBeGreaterThan(0);

    return seedHomeEntryFields();
}

test('bedrock:clear removes demo content while preserving the home entry', function (): void {
    $home = seededDemoState($this->assetsPath);

    $this->artisan('bedrock:clear', ['--force' => true])->assertExitCode(Command::SUCCESS);

    $remaining = EntryFacade::all();
    expect($remaining->count())->toBe(1)
        ->and($remaining->first()->id())->toBe($home->id());

    $freshHome = EntryFacade::find($home->id());
    expect($freshHome->get('blocks'))->toBeNull()
        ->and($freshHome->get('seo_title'))->toBeNull()
        ->and($freshHome->get('seo_description'))->toBeNull()
        ->and($freshHome->get('og_image'))->toBeNull()
        ->and($freshHome->get('twitter_image'))->toBeNull()
        ->and($freshHome->get('title'))->not->toBeNull();
});

test('bedrock:clear empties navigation trees, category terms and selected globals', function (): void {
    seededDemoState($this->assetsPath);

    $this->artisan('bedrock:clear', ['--force' => true])->assertExitCode(Command::SUCCESS);

    foreach (Nav::all() as $nav) {
        foreach (Site::all()->map->handle() as $siteHandle) {
            expect($nav->in($siteHandle)?->tree() ?? [])->toBe([]);
        }
    }

    expect(Term::whereTaxonomy('categories')->count())->toBe(0);

    foreach (clearedGlobalSetHandles() as $handle) {
        expect(GlobalVariables::whereSet($handle)->count())->toBe(0);
    }
});

test('bedrock:clear deletes demo assets but keeps logos', function (): void {
    seededDemoState($this->assetsPath);
    $assetIds = seedDemoAssets($this->assetsPath);

    $this->artisan('bedrock:clear', ['--force' => true])->assertExitCode(Command::SUCCESS);

    expect(Asset::find($assetIds['image']))->toBeNull()
        ->and(Asset::find($assetIds['avatar']))->toBeNull()
        ->and(Asset::find($assetIds['logo']))->not->toBeNull()
        ->and(Asset::whereFolder('logos', 'assets')->count())->toBeGreaterThan(0)
        ->and($this->assetsPath.'/.gitkeep')->toBeFile();
});

test('bedrock:clear leaves the real content and assets untouched', function (): void {
    seededDemoState($this->assetsPath);

    $contentFiles = countFilesIn($this->realContentPath);
    $assetFiles = countFilesIn($this->realAssetsPath);

    $this->artisan('bedrock:clear', ['--force' => true])->assertExitCode(Command::SUCCESS);

    expect(countFilesIn($this->realContentPath))->toBe($contentFiles)
        ->and(countFilesIn($this->realAssetsPath))->toBe($assetFiles)
        ->and($this->realContentPath.'/collections/pages/home.md')->toBeFile();
});
