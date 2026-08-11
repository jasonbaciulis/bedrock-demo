<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Structures\Nav as NavContract;
use Statamic\Facades\Asset;
use Statamic\Facades\Entry as EntryFacade;
use Statamic\Facades\GlobalVariables;
use Statamic\Facades\Nav;
use Statamic\Facades\Site;
use Statamic\Facades\Stache;
use Statamic\Facades\Term;
use Statamic\Stache\Stores\Store;
use Symfony\Component\Finder\SplFileInfo;
use Tests\Feature\Console\Support\Scratch;
use Tests\Feature\Console\Support\TestEntry;

beforeEach(function (): void {
    $this->realContentPath = base_path('content');
    $this->realAssetsPath = public_path('assets');
    $this->scratchPath = Scratch::path();
    $this->contentPath = $this->scratchPath.'/content-root/content';
    $this->assetsPath = $this->scratchPath.'/content-root/public/assets';

    copyDemoContent($this->realContentPath, $this->contentPath);
    File::ensureDirectoryExists($this->assetsPath);
    File::put($this->assetsPath.'/.gitkeep', '');

    pointStatamicAtScratchContent($this->contentPath, $this->assetsPath);

    // The command deletes content, so prove the repoint took effect before it runs.
    expect(Str::startsWith(Stache::store('entries')->directory(), $this->realContentPath))->toBeFalse();
});

afterEach(function (): void {
    Scratch::delete();
});

/**
 * Copy the demo content, minus the entries other parallel processes seed into
 * the same tree. Those files appear and vanish while this copy runs.
 */
function copyDemoContent(string $source, string $destination): void
{
    demoFilesIn($source)->each(function (SplFileInfo $file) use ($destination): void {
        $target = $destination.'/'.$file->getRelativePathname();

        File::ensureDirectoryExists(dirname($target));
        File::copy($file->getPathname(), $target);
    });
}

/**
 * @return Collection<int, SplFileInfo>
 */
function demoFilesIn(string $path): Collection
{
    return collect(File::allFiles($path))
        ->reject(fn (SplFileInfo $file): bool => Str::startsWith($file->getFilename(), 'test-'));
}

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

function countDemoFilesIn(string $path): int
{
    return demoFilesIn($path)->count();
}

/**
 * Assert the copied demo content holds everything the command is meant to
 * clear, then seed the home entry fields. A missing fixture fails here instead
 * of turning a later assertion into a false pass.
 */
function seededDemoState(): Entry
{
    expect(EntryFacade::all()->count())->toBeGreaterThan(1)
        ->and(Term::whereTaxonomy('categories')->count())->toBeGreaterThan(0);

    foreach (clearedGlobalSetHandles() as $handle) {
        expect(GlobalVariables::whereSet($handle)->count())->toBeGreaterThan(0);
    }

    expect(Nav::all()->flatMap(
        fn (NavContract $nav): Collection => Site::all()->map->handle()->map(
            fn (string $siteHandle): array => $nav->in($siteHandle)?->tree() ?? []
        )
    )->flatten()->count())->toBeGreaterThan(0);

    return seedHomeEntryFields();
}

test('bedrock:clear removes demo content while preserving the home entry', function (): void {
    $home = seededDemoState();

    $this->artisan('bedrock:clear', ['--force' => true])->assertSuccessful();

    $remaining = EntryFacade::all();
    expect($remaining->count())->toBe(1)
        ->and($remaining->first()->id())->toBe($home->id());

    $freshHome = TestEntry::fresh($home->id());

    expect($freshHome->data()->all())
        ->not->toHaveKeys(['blocks', 'seo_title', 'seo_description', 'og_image', 'twitter_image'])
        ->toHaveKey('title');
});

test('bedrock:clear empties navigation trees, category terms and selected globals', function (): void {
    seededDemoState();

    $this->artisan('bedrock:clear', ['--force' => true])->assertSuccessful();

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
    seededDemoState();
    $assetIds = seedDemoAssets($this->assetsPath);

    $this->artisan('bedrock:clear', ['--force' => true])->assertSuccessful();

    expect(Asset::find($assetIds['image']))->toBeNull()
        ->and(Asset::find($assetIds['avatar']))->toBeNull()
        ->and(Asset::find($assetIds['logo']))->not->toBeNull()
        ->and(Asset::whereFolder('logos', 'assets')->count())->toBeGreaterThan(0)
        ->and($this->assetsPath.'/.gitkeep')->toBeFile();
});

test('bedrock:clear leaves the real content and assets untouched', function (): void {
    seededDemoState();

    $contentFiles = countDemoFilesIn($this->realContentPath);
    $assetFiles = countDemoFilesIn($this->realAssetsPath);

    $this->artisan('bedrock:clear', ['--force' => true])->assertSuccessful();

    expect(countDemoFilesIn($this->realContentPath))->toBe($contentFiles)
        ->and(countDemoFilesIn($this->realAssetsPath))->toBe($assetFiles)
        ->and($this->realContentPath.'/collections/pages/home.md')->toBeFile();
});
