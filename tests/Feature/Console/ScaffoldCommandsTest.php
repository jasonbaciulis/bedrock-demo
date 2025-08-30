<?php

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Laravel\Prompts\ConfirmPrompt;
use Laravel\Prompts\Prompt;
use Statamic\Facades\Config as StatamicConfig;
use Statamic\Facades\YAML;
use Statamic\Facades\Entry;

beforeAll(function () {
    // Always auto-confirm destructive prompts in tests.
    Prompt::fallbackWhen(true);
    ConfirmPrompt::fallbackUsing(fn() => true);
});

beforeEach(function () {
    // Snapshot YAMLs so we can restore after each test.
    $this->blocksYamlPath = base_path('resources/fieldsets/blocks.yaml');
    $this->articleYamlPath = base_path('resources/fieldsets/article.yaml');

    $this->originalBlocksYaml = is_file($this->blocksYamlPath)
        ? file_get_contents($this->blocksYamlPath)
        : '';
    $this->originalArticleYaml = is_file($this->articleYamlPath)
        ? file_get_contents($this->articleYamlPath)
        : '';
});

afterEach(function () {
    // Restore YAMLs
    if ($this->originalBlocksYaml !== '') {
        file_put_contents($this->blocksYamlPath, $this->originalBlocksYaml);
    }
    if ($this->originalArticleYaml !== '') {
        file_put_contents($this->articleYamlPath, $this->originalArticleYaml);
    }

    // Remove any scaffolding files created by tests.
    $globPaths = [
        base_path('resources/fieldsets/scaffold_test_*.yaml'),
        base_path('resources/views/blocks/scaffold-test-*.blade.php'),
        base_path('resources/views/sets/scaffold-test-*.blade.php'),
        base_path('content/collections/pages/test-page*.md'),
        base_path('content/collections/posts/test-post*.md'),
    ];

    foreach ($globPaths as $pattern) {
        foreach (glob($pattern) ?: [] as $file) {
            @unlink($file);
        }
    }
});

function parseYaml(string $path): array
{
    return YAML::file($path)->parse() ?? [];
}

function findFieldIndexByHandle(array $data, string $handle): int
{
    foreach ($data['fields'] ?? [] as $index => $field) {
        if (($field['handle'] ?? null) === $handle) {
            return $index;
        }
    }

    return -1;
}

test('make:block creates files and updates blocks.yaml', function () {
    $group = 'messaging';
    $name = 'Scaffold Test Block ' . Str::random(6);
    $instructions = 'Test instructions';

    $locale = StatamicConfig::getShortLocale();
    $fieldset = Str::slug($name, '_', $locale);
    $view = Str::slug($name, '-', $locale);

    $this->artisan('make:block', [
        'group' => $group,
        'name' => $name,
        '--instructions' => $instructions,
        '--force' => true,
    ])->assertExitCode(Command::SUCCESS);

    $fieldsetPath = base_path("resources/fieldsets/{$fieldset}.yaml");
    $viewPath = base_path("resources/views/blocks/{$view}.blade.php");

    expect(is_file($fieldsetPath))->toBeTrue();
    expect(is_file($viewPath))->toBeTrue();

    $data = parseYaml($this->blocksYamlPath);
    $idx = findFieldIndexByHandle($data, 'blocks');
    expect($idx)->toBeGreaterThan(-1);

    $config = $data['fields'][$idx]['field']['sets'][$group]['sets'][$fieldset] ?? null;
    expect($config)->not->toBeNull();
    expect($config['display'] ?? null)->toBe($name);
    expect($config['instructions'] ?? null)->toBe($instructions);
    expect($config['fields'][0]['import'] ?? null)->toBe($fieldset);
});

test('make:set creates files and updates article.yaml', function () {
    $group = 'text_layout';
    $name = 'Scaffold Test Set ' . Str::random(6);
    $instructions = 'Test instructions';

    $locale = StatamicConfig::getShortLocale();
    $fieldset = Str::slug($name, '_', $locale);
    $view = Str::slug($name, '-', $locale);

    $this->artisan('make:set', [
        'group' => $group,
        'name' => $name,
        '--instructions' => $instructions,
        '--force' => true,
    ])->assertExitCode(Command::SUCCESS);

    $fieldsetPath = base_path("resources/fieldsets/{$fieldset}.yaml");
    $viewPath = base_path("resources/views/sets/{$view}.blade.php");

    expect(is_file($fieldsetPath))->toBeTrue();
    expect(is_file($viewPath))->toBeTrue();

    $data = parseYaml($this->articleYamlPath);
    $idx = findFieldIndexByHandle($data, 'article');
    expect($idx)->toBeGreaterThan(-1);

    $config = $data['fields'][$idx]['field']['sets'][$group]['sets'][$fieldset] ?? null;
    expect($config)->not->toBeNull();
    expect($config['display'] ?? null)->toBe($name);
    expect($config['instructions'] ?? null)->toBe($instructions);
    expect($config['fields'][0]['import'] ?? null)->toBe($fieldset);
});

test('delete:block removes from blocks.yaml and deletes files', function () {
    $group = 'messaging';
    $name = 'Scaffold Test Block ' . Str::random(6);
    $locale = StatamicConfig::getShortLocale();
    $fieldset = Str::slug($name, '_', $locale);
    $view = Str::slug($name, '-', $locale);

    // Create first
    $this->artisan('make:block', [
        'group' => $group,
        'name' => $name,
        '--instructions' => 'irrelevant',
        '--force' => true,
    ])->assertExitCode(Command::SUCCESS);

    // Create a page entry that uses the block so we exercise usage removal and message still confirms
    /** @var \Statamic\Entries\Entry $entry */
    $entry = Entry::make();
    $entry->collection('pages');
    $entry->id('test-page-' . Str::random(6));
    $entry->slug('test-page');
    $entry->data([
        'title' => 'Test Page',
        'blocks' => [['type' => $fieldset, 'enabled' => true]],
    ]);
    $entry->save();
    $entryId = $entry->id();

    // Then delete
    $this->artisan('delete:block', [
        'group' => $group,
        'block' => $fieldset,
    ])
        ->expectsConfirmation("Delete '{$name}' from 'Messaging' group?", 'yes')
        ->assertExitCode(Command::SUCCESS);

    // Entry usages should be removed
    /** @var \Statamic\Entries\Entry|null $updated */
    $updated = Entry::find($entryId);
    expect($updated)->not->toBeNull();
    $blocks = (array) $updated->data()->get('blocks');
    $hasBlock = collect($blocks)->contains(
        fn($i) => is_array($i) && ($i['type'] ?? null) === $fieldset
    );
    expect($hasBlock)->toBeFalse();

    $fieldsetPath = base_path("resources/fieldsets/{$fieldset}.yaml");
    $viewPath = base_path("resources/views/blocks/{$view}.blade.php");

    expect(is_file($fieldsetPath))->toBeFalse();
    expect(is_file($viewPath))->toBeFalse();

    $data = parseYaml($this->blocksYamlPath);
    $idx = findFieldIndexByHandle($data, 'blocks');
    expect($idx)->toBeGreaterThan(-1);
    $exists = isset($data['fields'][$idx]['field']['sets'][$group]['sets'][$fieldset]);
    expect($exists)->toBeFalse();
});

test('delete:block with --keep-files removes blocks.yaml but keeps files', function () {
    $group = 'messaging';
    $name = 'Scaffold Test Block ' . Str::random(6);
    $locale = StatamicConfig::getShortLocale();
    $fieldset = Str::slug($name, '_', $locale);
    $view = Str::slug($name, '-', $locale);

    $this->artisan('make:block', [
        'group' => $group,
        'name' => $name,
        '--instructions' => 'irrelevant',
        '--force' => true,
    ])->assertExitCode(Command::SUCCESS);

    $fieldsetPath = base_path("resources/fieldsets/{$fieldset}.yaml");
    $viewPath = base_path("resources/views/blocks/{$view}.blade.php");
    expect(is_file($fieldsetPath))->toBeTrue();
    expect(is_file($viewPath))->toBeTrue();

    $this->artisan('delete:block', [
        'group' => $group,
        'block' => $fieldset,
        '--keep-files' => true,
    ])
        ->expectsConfirmation("Delete '{$name}' from 'Messaging' group?", 'yes')
        ->assertExitCode(Command::SUCCESS);

    expect(is_file($fieldsetPath))->toBeTrue();
    expect(is_file($viewPath))->toBeTrue();

    $data = parseYaml($this->blocksYamlPath);
    $idx = findFieldIndexByHandle($data, 'blocks');
    expect($idx)->toBeGreaterThan(-1);
    $exists = isset($data['fields'][$idx]['field']['sets'][$group]['sets'][$fieldset]);
    expect($exists)->toBeFalse();

    // Cleanup leftover files explicitly
    @unlink($fieldsetPath);
    @unlink($viewPath);
});

test('delete:set removes from article.yaml and deletes files', function () {
    $group = 'text_layout';
    $name = 'Scaffold Test Set ' . Str::random(6);
    $locale = StatamicConfig::getShortLocale();
    $fieldset = Str::slug($name, '_', $locale);
    $view = Str::slug($name, '-', $locale);

    // Create first
    $this->artisan('make:set', [
        'group' => $group,
        'name' => $name,
        '--instructions' => 'irrelevant',
        '--force' => true,
    ])->assertExitCode(Command::SUCCESS);

    // Create a post entry that uses the set in Bard so we exercise usage removal
    /** @var \Statamic\Entries\Entry $entry */
    $entry = Entry::make();
    $entry->collection('posts');
    $entry->id('test-post-' . Str::random(6));
    $entry->slug('test-post');
    $entry->data([
        'title' => 'Test Post',
        'article' => [
            [
                'type' => 'set',
                'attrs' => [
                    'id' => 'abc',
                    'values' => [
                        'type' => $fieldset,
                    ],
                ],
            ],
        ],
    ]);
    $entry->save();
    $entryId = $entry->id();

    // Then delete
    $this->artisan('delete:set', [
        'group' => $group,
        'set' => $fieldset,
    ])
        ->expectsConfirmation("Delete '{$name}' from 'Text & Layout' group?", 'yes')
        ->assertExitCode(Command::SUCCESS);

    // Entry usages should be removed
    /** @var \Statamic\Entries\Entry|null $updated */
    $updated = Entry::find($entryId);
    expect($updated)->not->toBeNull();
    $article = (array) $updated->data()->get('article');
    $hasSet = collect($article)->contains(function ($node) use ($fieldset) {
        if (!is_array($node) || ($node['type'] ?? null) !== 'set') {
            return false;
        }
        return ($node['attrs']['values']['type'] ?? null) === $fieldset;
    });
    expect($hasSet)->toBeFalse();

    $fieldsetPath = base_path("resources/fieldsets/{$fieldset}.yaml");
    $viewPath = base_path("resources/views/sets/{$view}.blade.php");

    expect(is_file($fieldsetPath))->toBeFalse();
    expect(is_file($viewPath))->toBeFalse();

    $data = parseYaml($this->articleYamlPath);
    $idx = findFieldIndexByHandle($data, 'article');
    expect($idx)->toBeGreaterThan(-1);
    $exists = isset($data['fields'][$idx]['field']['sets'][$group]['sets'][$fieldset]);
    expect($exists)->toBeFalse();
});

test('delete:set with --keep-files removes from article.yaml but keeps files', function () {
    $group = 'text_layout';
    $name = 'Scaffold Test Set ' . Str::random(6);
    $locale = StatamicConfig::getShortLocale();
    $fieldset = Str::slug($name, '_', $locale);
    $view = Str::slug($name, '-', $locale);

    $this->artisan('make:set', [
        'group' => $group,
        'name' => $name,
        '--instructions' => 'irrelevant',
        '--force' => true,
    ])->assertExitCode(Command::SUCCESS);

    $fieldsetPath = base_path("resources/fieldsets/{$fieldset}.yaml");
    $viewPath = base_path("resources/views/sets/{$view}.blade.php");
    expect(is_file($fieldsetPath))->toBeTrue();
    expect(is_file($viewPath))->toBeTrue();

    $this->artisan('delete:set', [
        'group' => $group,
        'set' => $fieldset,
        '--keep-files' => true,
    ])
        ->expectsConfirmation("Delete '{$name}' from 'Text & Layout' group?", 'yes')
        ->assertExitCode(Command::SUCCESS);

    expect(is_file($fieldsetPath))->toBeTrue();
    expect(is_file($viewPath))->toBeTrue();

    $data = parseYaml($this->articleYamlPath);
    $idx = findFieldIndexByHandle($data, 'article');
    expect($idx)->toBeGreaterThan(-1);
    $exists = isset($data['fields'][$idx]['field']['sets'][$group]['sets'][$fieldset]);
    expect($exists)->toBeFalse();

    // Cleanup leftover files explicitly
    @unlink($fieldsetPath);
    @unlink($viewPath);
});

test('make:block without --force fails when files already exist', function () {
    $group = 'messaging';
    $name = 'Scaffold Test Block ' . Str::random(6);
    $locale = StatamicConfig::getShortLocale();
    $fieldset = Str::slug($name, '_', $locale);
    $view = Str::slug($name, '-', $locale);

    // First creation succeeds
    $this->artisan('make:block', [
        'group' => $group,
        'name' => $name,
        '--instructions' => 'irrelevant',
        '--force' => true,
    ])->assertExitCode(Command::SUCCESS);

    // Second should fail due to existing files
    $this->artisan('make:block', [
        'group' => $group,
        'name' => $name,
        '--instructions' => 'irrelevant',
    ])->assertExitCode(Command::FAILURE);

    // Cleanup created files
    @unlink(base_path("resources/fieldsets/{$fieldset}.yaml"));
    @unlink(base_path("resources/views/blocks/{$view}.blade.php"));
});

test('make:set without --force fails when files already exist', function () {
    $group = 'text_layout';
    $name = 'Scaffold Test Set ' . Str::random(6);
    $locale = StatamicConfig::getShortLocale();
    $fieldset = Str::slug($name, '_', $locale);
    $view = Str::slug($name, '-', $locale);

    // First creation succeeds
    $this->artisan('make:set', [
        'group' => $group,
        'name' => $name,
        '--instructions' => 'irrelevant',
        '--force' => true,
    ])->assertExitCode(Command::SUCCESS);

    // Second should fail due to existing files
    $this->artisan('make:set', [
        'group' => $group,
        'name' => $name,
        '--instructions' => 'irrelevant',
    ])->assertExitCode(Command::FAILURE);

    // Cleanup created files
    @unlink(base_path("resources/fieldsets/{$fieldset}.yaml"));
    @unlink(base_path("resources/views/sets/{$view}.blade.php"));
});
