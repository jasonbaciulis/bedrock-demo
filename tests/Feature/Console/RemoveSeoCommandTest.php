<?php

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Laravel\Prompts\ConfirmPrompt;
use Laravel\Prompts\Prompt;
use Statamic\Facades\Entry;
use Statamic\Facades\YAML;

beforeAll(function (): void {
    // Always auto-confirm destructive prompts in tests.
    Prompt::fallbackWhen(true);
    ConfirmPrompt::fallbackUsing(fn (): true => true);
});

beforeEach(function (): void {
    setUpSeoRemovalScratch();

    $this->base = bedrockSeoScratchBase();
    $this->fieldsetsPath = config('statamic.bedrock.scaffold.fieldsets_path');

    // Test-only handles so entry stripping only touches the seeded entry,
    // never real demo content.
    $this->seoTitleHandle = 'seo_title_test';
    $this->ogImageHandle = 'og_image_test';

    writeSeoFieldset($this->fieldsetsPath, 'seo_basic', [$this->seoTitleHandle]);
    writeSeoFieldset($this->fieldsetsPath, 'seo_open_graph', [$this->ogImageHandle]);
    foreach (['seo_advanced', 'seo_json-ld_schema', 'seo_sitemap'] as $fieldset) {
        writeSeoFieldset($this->fieldsetsPath, $fieldset, []);
    }

    $entry = Entry::make();
    $entry->collection('pages');
    $entry->id($this->entryId = bedrockTestEntryId('test-page'));
    $entry->slug($this->entryId);
    $entry->data([
        'title' => 'Test Page',
        $this->seoTitleHandle => 'Custom title',
        $this->ogImageHandle => 'og.jpg',
    ]);
    $entry->save();
});

afterEach(function (): void {
    File::deleteDirectory(bedrockTestScratchPath());

    foreach (glob(base_path('content/collections/pages/test-page-*.md')) ?: [] as $file) {
        @unlink($file);
    }
});

/**
 * Scratch root the bedrock:remove-seo command is pointed at so it mutates an
 * isolated copy of the kit files instead of the real repo.
 */
function bedrockSeoScratchBase(): string
{
    return bedrockTestScratchPath().'/seo-root';
}

/**
 * Copy the files bedrock:remove-seo edits/deletes into the scratch root and
 * rebind config so the command operates there. The SEO fieldsets are seeded
 * separately in beforeEach with test-only handles.
 */
function setUpSeoRemovalScratch(): void
{
    $base = bedrockSeoScratchBase();

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
        'content/seo.yaml',
        'content/globals/seo.yaml',
        'content/globals/default/seo.yaml',
        'vite.config.js',
    ];

    foreach ($files as $relative) {
        $source = base_path($relative);

        if (! is_file($source)) {
            continue;
        }

        $destination = "{$base}/{$relative}";
        File::ensureDirectoryExists(dirname($destination));
        File::copy($source, $destination);
    }

    File::ensureDirectoryExists("{$base}/resources/fieldsets");

    config([
        'statamic.bedrock.seo_removal.base_path' => $base,
        'statamic.bedrock.scaffold.fieldsets_path' => "{$base}/resources/fieldsets",
    ]);
}

function writeSeoFieldset(string $dir, string $handle, array $fieldHandles): void
{
    $fields = array_map(fn (string $field): array => [
        'handle' => $field,
        'field' => ['type' => 'text'],
    ], $fieldHandles);

    File::put("{$dir}/{$handle}.yaml", YAML::dump([
        'title' => $handle,
        'fields' => $fields,
    ]));
}

test('bedrock:remove-seo deletes seo files and fieldsets', function (): void {
    $this->artisan('bedrock:remove-seo', ['--force' => true])
        ->assertExitCode(Command::SUCCESS);

    $deleted = [
        'resources/blueprints/globals/seo.yaml',
        'resources/views/partials/seo.antlers.html',
        'resources/views/partials/fallback-description.antlers.html',
        'resources/views/partials/cookie-dialog.antlers.html',
        'resources/js/components/cookieDialog.js',
        'content/seo.yaml',
        'content/globals/seo.yaml',
        'content/globals/default/seo.yaml',
    ];

    foreach ($deleted as $relative) {
        expect("{$this->base}/{$relative}")->not->toBeFile();
    }

    foreach (['seo_basic', 'seo_advanced', 'seo_open_graph', 'seo_json-ld_schema', 'seo_sitemap'] as $fieldset) {
        expect("{$this->fieldsetsPath}/{$fieldset}.yaml")->not->toBeFile();
    }
});

test('bedrock:remove-seo removes the seo tab from collection blueprints', function (): void {
    $this->artisan('bedrock:remove-seo', ['--force' => true])
        ->assertExitCode(Command::SUCCESS);

    foreach (['pages/page', 'posts/post'] as $blueprint) {
        $data = YAML::file("{$this->base}/resources/blueprints/collections/{$blueprint}.yaml")->parse();
        expect($data['tabs'])->not->toHaveKey('seo');
    }
});

test('bedrock:remove-seo cleans template and build references', function (): void {
    $this->artisan('bedrock:remove-seo', ['--force' => true])
        ->assertExitCode(Command::SUCCESS);

    $layout = File::get("{$this->base}/resources/views/layout.antlers.html");
    expect($layout)->not->toContain('partials.seo')
        ->and($layout)->not->toContain('yield:seo_body');

    $footer = File::get("{$this->base}/resources/views/partials/nav-bottom-footer.antlers.html");
    expect($footer)->not->toContain('reset_cookie_consent');

    $social = File::get("{$this->base}/resources/views/partials/social-sharing.antlers.html");
    expect($social)->not->toContain('seo:twitter_site');

    $vite = File::get("{$this->base}/vite.config.js");
    expect($vite)->not->toContain('cookieDialog.js');
});

test('bedrock:remove-seo strips seo keys from existing entries', function (): void {
    $this->artisan('bedrock:remove-seo', ['--force' => true])
        ->assertExitCode(Command::SUCCESS);

    $entry = Entry::find($this->entryId);

    expect($entry)->not->toBeNull()
        ->and($entry->has($this->seoTitleHandle))->toBeFalse()
        ->and($entry->has($this->ogImageHandle))->toBeFalse()
        ->and($entry->get('title'))->toBe('Test Page');
});
