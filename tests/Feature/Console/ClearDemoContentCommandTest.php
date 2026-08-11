<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
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
use Tests\Feature\Console\Support\Scratch;
use Tests\Feature\Console\Support\TestEntry;

beforeEach(function (): void {
    $this->realContentPath = base_path('content');
    $this->realAssetsPath = public_path('assets');
    $this->assetsPath = Scratch::path().'/content-root/public/assets';

    File::ensureDirectoryExists($this->assetsPath);
    File::put($this->assetsPath.'/.gitkeep', '');

    // Repoint the disk before isolateContentTree() clears the Stache, so the
    // asset store never caches the real assets directory.
    config(['filesystems.disks.assets.root' => $this->assetsPath]);
    Scratch::isolateContentTree();

    // The command deletes content and assets, so prove both repoints took
    // effect before it runs.
    expect(Str::startsWith(Stache::store('entries')->directory(), $this->realContentPath))->toBeFalse()
        ->and(Str::startsWith(Storage::disk('assets')->path(''), $this->realAssetsPath))->toBeFalse();
});

afterEach(function (): void {
    Scratch::delete();
});

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
    return Scratch::demoFilesIn($path)->count();
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

test('bedrock:clear keeps everything when the confirmation is declined', function (): void {
    $entryCount = EntryFacade::all()->count();

    $this->artisan('bedrock:clear')
        ->expectsConfirmation('This will delete all Bedrock demo content. Continue?', 'no')
        ->expectsOutputToContain('Aborted.')
        ->assertSuccessful();

    expect(EntryFacade::all()->count())->toBe($entryCount)
        ->and(Term::whereTaxonomy('categories')->count())->toBeGreaterThan(0);
});

test('bedrock:clear runs after the confirmation is accepted', function (): void {
    seededDemoState();

    $this->artisan('bedrock:clear')
        ->expectsConfirmation('This will delete all Bedrock demo content. Continue?', 'yes')
        ->assertSuccessful();

    expect(EntryFacade::all()->count())->toBe(1);
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
