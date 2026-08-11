<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Statamic\Facades\Fieldset;
use Statamic\Facades\YAML;
use Tests\Feature\Console\Support\Scratch;
use Tests\Feature\Console\Support\TestEntry;

beforeEach(function (): void {
    $this->projectRoot = base_path();
    $this->scratchRoot = Scratch::path().'/seo-root';
    $this->fieldsetsPath = $this->scratchRoot.'/resources/fieldsets';

    copyKitFilesForSeoRemoval($this->scratchRoot);
    pointApplicationAtScratchRoot($this->scratchRoot, $this->fieldsetsPath);

    // The command deletes files, so prove the repoint took effect before it runs.
    expect(base_path())->not->toBe($this->projectRoot);

    // Test-only handles so entry stripping only touches the seeded entry,
    // never real demo content.
    $this->seoTitleHandle = 'seo_title_test';
    $this->ogImageHandle = 'og_image_test';

    seedSeoFieldsets($this->fieldsetsPath, [
        'seo_basic' => [$this->seoTitleHandle],
        'seo_open_graph' => [$this->ogImageHandle],
        'seo_advanced' => [],
        'seo_json-ld_schema' => [],
        'seo_sitemap' => [],
    ]);

    $this->entryId = TestEntry::create('pages', [
        'title' => 'Test Page',
        $this->seoTitleHandle => 'Custom title',
        $this->ogImageHandle => 'og.jpg',
    ])->id();
});

afterEach(function (): void {
    $this->app->setBasePath($this->projectRoot);

    Scratch::delete();
    TestEntry::deleteAll();
});

/**
 * Copy every file bedrock:remove-seo edits or deletes into an isolated root, so
 * the command mutates a copy of the kit instead of the real repo.
 */
function copyKitFilesForSeoRemoval(string $scratchRoot): void
{
    $files = [
        'resources/blueprints/globals/seo.yaml',
        'resources/blueprints/collections/pages/page.yaml',
        'resources/blueprints/collections/posts/post.yaml',
        'resources/views/layout.antlers.html',
        'resources/views/partials/seo.antlers.html',
        'resources/views/partials/fallback-description.antlers.html',
        'resources/views/partials/cookie-dialog.antlers.html',
        'resources/views/partials/nav-bottom-footer.antlers.html',
        'resources/views/partials/social-sharing.antlers.html',
        'resources/js/components/cookieDialog.js',
        'content/globals/seo.yaml',
        'content/globals/default/seo.yaml',
        'vite.config.js',
    ];

    foreach ($files as $relative) {
        $source = base_path($relative);

        if (! is_file($source)) {
            continue;
        }

        $destination = "{$scratchRoot}/{$relative}";
        File::ensureDirectoryExists(dirname($destination));
        File::copy($source, $destination);
    }

    File::copyDirectory(base_path('resources/fieldsets'), "{$scratchRoot}/resources/fieldsets");
}

/**
 * Repoint the application root so every base_path() call inside the command
 * lands in the scratch copy, and the fieldset repository with it, because the
 * command deletes fieldsets through the facade. Storage stays real, because the
 * log and cache stores write there while the command runs.
 */
function pointApplicationAtScratchRoot(string $scratchRoot, string $fieldsetsPath): void
{
    $storagePath = storage_path();

    Fieldset::setDirectory($fieldsetsPath);
    app()->setBasePath($scratchRoot);
    app()->useStoragePath($storagePath);
}

/**
 * @param  array<string, list<string>>  $fieldsets  Fieldset handle to its field handles
 */
function seedSeoFieldsets(string $dir, array $fieldsets): void
{
    foreach ($fieldsets as $handle => $fieldHandles) {
        $fields = array_map(fn (string $field): array => [
            'handle' => $field,
            'field' => ['type' => 'text'],
        ], $fieldHandles);

        File::put("{$dir}/{$handle}.yaml", YAML::dump([
            'title' => $handle,
            'fields' => $fields,
        ]));
    }
}

test('bedrock:remove-seo deletes seo files and fieldsets', function (): void {
    $this->artisan('bedrock:remove-seo', ['--force' => true])->assertSuccessful();

    $deleted = [
        'resources/blueprints/globals/seo.yaml',
        'resources/views/partials/seo.antlers.html',
        'resources/views/partials/fallback-description.antlers.html',
        'resources/views/partials/cookie-dialog.antlers.html',
        'resources/js/components/cookieDialog.js',
        'content/globals/seo.yaml',
        'content/globals/default/seo.yaml',
        'resources/fieldsets/seo_basic.yaml',
        'resources/fieldsets/seo_advanced.yaml',
        'resources/fieldsets/seo_open_graph.yaml',
        'resources/fieldsets/seo_json-ld_schema.yaml',
        'resources/fieldsets/seo_sitemap.yaml',
    ];

    foreach ($deleted as $relative) {
        expect("{$this->scratchRoot}/{$relative}")->not->toBeFile();
    }
});

test('bedrock:remove-seo leaves the real project files untouched', function (): void {
    $this->artisan('bedrock:remove-seo', ['--force' => true])->assertSuccessful();

    $untouched = [
        'resources/blueprints/globals/seo.yaml',
        'resources/views/partials/seo.antlers.html',
        'resources/js/components/cookieDialog.js',
        'resources/fieldsets/seo_basic.yaml',
        'vite.config.js',
    ];

    foreach ($untouched as $relative) {
        expect("{$this->projectRoot}/{$relative}")->toBeFile();
    }

    expect(File::get("{$this->projectRoot}/resources/views/layout.antlers.html"))
        ->toContain('partials.seo')
        ->and(File::get("{$this->projectRoot}/vite.config.js"))
        ->toContain('cookieDialog.js');
});

test('bedrock:remove-seo removes the seo tab from collection blueprints', function (): void {
    $this->artisan('bedrock:remove-seo', ['--force' => true])->assertSuccessful();

    foreach (['pages/page', 'posts/post'] as $blueprint) {
        $data = YAML::file("{$this->scratchRoot}/resources/blueprints/collections/{$blueprint}.yaml")->parse();
        expect($data['tabs'])->not->toHaveKey('seo');
    }
});

test('bedrock:remove-seo sweeps blueprints and site globals it does not know by name', function (): void {
    $blueprintPath = "{$this->scratchRoot}/resources/blueprints/collections/services/service.yaml";
    File::ensureDirectoryExists(dirname($blueprintPath));
    File::put($blueprintPath, YAML::dump([
        'title' => 'Service',
        'tabs' => [
            'main' => ['display' => 'Main'],
            'seo' => ['display' => 'SEO'],
        ],
    ]));

    $siteGlobalsPath = "{$this->scratchRoot}/content/globals/french/seo.yaml";
    File::ensureDirectoryExists(dirname($siteGlobalsPath));
    File::put($siteGlobalsPath, YAML::dump(['data' => []]));

    $this->artisan('bedrock:remove-seo', ['--force' => true])->assertSuccessful();

    $data = YAML::file($blueprintPath)->parse();
    expect($data['tabs'])->not->toHaveKey('seo')
        ->and($data['tabs'])->toHaveKey('main')
        ->and($siteGlobalsPath)->not->toBeFile();
});

test('bedrock:remove-seo cleans template and build references', function (): void {
    $this->artisan('bedrock:remove-seo', ['--force' => true])->assertSuccessful();

    $layout = File::get("{$this->scratchRoot}/resources/views/layout.antlers.html");
    expect($layout)->not->toContain('partials.seo')
        ->and($layout)->not->toContain('yield:seo_body');

    $footer = File::get("{$this->scratchRoot}/resources/views/partials/nav-bottom-footer.antlers.html");
    expect($footer)->not->toContain('reset_cookie_consent');

    $social = File::get("{$this->scratchRoot}/resources/views/partials/social-sharing.antlers.html");
    expect($social)->not->toContain('seo:twitter_site');

    $vite = File::get("{$this->scratchRoot}/vite.config.js");
    expect($vite)->not->toContain('cookieDialog.js');
});

test('bedrock:remove-seo strips seo keys from existing entries', function (): void {
    $this->artisan('bedrock:remove-seo', ['--force' => true])->assertSuccessful();

    $entry = TestEntry::fresh($this->entryId);

    expect($entry->data()->all())
        ->not->toHaveKeys([$this->seoTitleHandle, $this->ogImageHandle])
        ->toHaveKey('title', 'Test Page');
});
