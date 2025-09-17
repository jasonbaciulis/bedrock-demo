<?php

use App\Support\Yaml\ArticleYaml;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Laravel\Prompts\ConfirmPrompt;
use Laravel\Prompts\Prompt;
use Statamic\Facades\Config as StatamicConfig;
use Statamic\Facades\Entry;

beforeAll(function () {
    // Always auto-confirm prompts in tests, except for optional group move.
    Prompt::fallbackWhen(true);
    ConfirmPrompt::fallbackUsing(function ($prompt = null) {
        $label = method_exists($prompt, 'label') ? (string) $prompt->label() : '';
        // Say "no" to optional group move to avoid extra select prompts.
        if (str_contains(strtolower($label), 'move this set to a different group')) {
            return false;
        }
        // Default to yes for other confirmations (e.g. rename confirmation).
        return true;
    });
});

beforeEach(function () {
    // Snapshot YAML so we can restore after each test.
    $this->articleYamlPath = base_path('resources/fieldsets/article.yaml');

    $this->originalArticleYaml = is_file($this->articleYamlPath)
        ? file_get_contents($this->articleYamlPath)
        : '';
});

afterEach(function () {
    // Restore YAML
    if ($this->originalArticleYaml !== '') {
        file_put_contents($this->articleYamlPath, $this->originalArticleYaml);
    }

    // Remove any scaffolding files created by tests.
    $globPaths = [
        base_path('resources/fieldsets/scaffold_test_*.yaml'),
        base_path('resources/fieldsets/scaffold_renamed_*.yaml'),
        base_path('resources/views/sets/scaffold-test-*.blade.php'),
        base_path('resources/views/sets/scaffold-renamed-*.blade.php'),
        base_path('content/collections/posts/test-post*.md'),
    ];

    foreach ($globPaths as $pattern) {
        foreach (glob($pattern) ?: [] as $file) {
            @unlink($file);
        }
    }
});

test('rename:bedrock-set renames files and updates article.yaml', function () {
    $group = 'text_layout';
    $originalName = 'Scaffold Test Set ' . Str::random(6);
    $newName = 'Scaffold Renamed Set ' . Str::random(6);

    $locale = StatamicConfig::getShortLocale();
    $originalFieldset = Str::slug($originalName, '_', $locale);
    $originalView = Str::slug($originalName, '-', $locale);
    $newFieldset = Str::slug($newName, '_', $locale);
    $newView = Str::slug($newName, '-', $locale);

    // First create a set
    $this->artisan('make:bedrock-set', [
        'group' => $group,
        'name' => $originalName,
        '--instructions' => 'Test instructions',
        '--force' => true,
    ])->assertExitCode(Command::SUCCESS);

    $originalFieldsetPath = base_path("resources/fieldsets/{$originalFieldset}.yaml");
    $originalViewPath = base_path("resources/views/sets/{$originalView}.blade.php");
    $newFieldsetPath = base_path("resources/fieldsets/{$newFieldset}.yaml");
    $newViewPath = base_path("resources/views/sets/{$newView}.blade.php");

    // Verify original files exist
    expect(is_file($originalFieldsetPath))->toBeTrue();
    expect(is_file($originalViewPath))->toBeTrue();

    // Now rename the set
    $this->artisan('rename:bedrock-set', [
        'group' => $group,
        'current_name' => $originalFieldset,
        'new_name' => $newName,
        '--force' => true,
    ])->assertExitCode(Command::SUCCESS);

    // Verify old files are gone and new files exist
    expect(is_file($originalFieldsetPath))->toBeFalse();
    expect(is_file($originalViewPath))->toBeFalse();
    expect(is_file($newFieldsetPath))->toBeTrue();
    expect(is_file($newViewPath))->toBeTrue();

    // Verify article.yaml is updated using ArticleYaml class
    $article = app(ArticleYaml::class);
    $sets = $article->sets($group);

    expect(isset($sets[$originalFieldset]))->toBeFalse();
    expect(isset($sets[$newFieldset]))->toBeTrue();
    expect($sets[$newFieldset])->toBe($newName);
});

test('rename:bedrock-set updates content entries', function () {
    $group = 'text_layout';
    $originalName = 'Scaffold Test Set ' . Str::random(6);
    $newName = 'Scaffold Renamed Set ' . Str::random(6);

    $locale = StatamicConfig::getShortLocale();
    $originalFieldset = Str::slug($originalName, '_', $locale);
    $newFieldset = Str::slug($newName, '_', $locale);

    // First create a set
    $this->artisan('make:bedrock-set', [
        'group' => $group,
        'name' => $originalName,
        '--instructions' => 'Test instructions',
        '--force' => true,
    ])->assertExitCode(Command::SUCCESS);

    // Create a post entry that uses the set in Bard
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
                        'type' => $originalFieldset,
                    ],
                ],
            ],
        ],
    ]);
    $entry->save();
    $entryId = $entry->id();

    // Now rename the set
    $this->artisan('rename:bedrock-set', [
        'group' => $group,
        'current_name' => $originalFieldset,
        'new_name' => $newName,
        '--force' => true,
    ])->assertExitCode(Command::SUCCESS);

    // Verify entry is updated with new set type
    /** @var \Statamic\Entries\Entry|null $updated */
    $updated = Entry::find($entryId);
    expect($updated)->not->toBeNull();

    $article = (array) $updated->data()->get('article');
    $hasOldSet = collect($article)->contains(function ($node) use ($originalFieldset) {
        if (!is_array($node) || ($node['type'] ?? null) !== 'set') {
            return false;
        }
        return ($node['attrs']['values']['type'] ?? null) === $originalFieldset;
    });
    $hasNewSet = collect($article)->contains(function ($node) use ($newFieldset) {
        if (!is_array($node) || ($node['type'] ?? null) !== 'set') {
            return false;
        }
        return ($node['attrs']['values']['type'] ?? null) === $newFieldset;
    });

    expect($hasOldSet)->toBeFalse();
    expect($hasNewSet)->toBeTrue();
});

test('rename:bedrock-set fails when target files exist without --force', function () {
    $group = 'text_layout';
    $originalName = 'Scaffold Test Set ' . Str::random(6);
    $newName = 'Scaffold Renamed Set ' . Str::random(6);

    $locale = StatamicConfig::getShortLocale();
    $originalFieldset = Str::slug($originalName, '_', $locale);
    $newFieldset = Str::slug($newName, '_', $locale);

    // Create original set
    $this->artisan('make:bedrock-set', [
        'group' => $group,
        'name' => $originalName,
        '--instructions' => 'Test instructions',
        '--force' => true,
    ])->assertExitCode(Command::SUCCESS);

    // Create another set with the target name
    $this->artisan('make:bedrock-set', [
        'group' => $group,
        'name' => $newName,
        '--instructions' => 'Test instructions',
        '--force' => true,
    ])->assertExitCode(Command::SUCCESS);

    // Rename should fail without --force
    $this->artisan('rename:bedrock-set', [
        'group' => $group,
        'current_name' => $originalFieldset,
        'new_name' => $newName,
    ])->assertExitCode(Command::FAILURE);

    // Cleanup created files
    @unlink(base_path("resources/fieldsets/{$originalFieldset}.yaml"));
    @unlink(base_path("resources/views/sets/" . str_replace('_', '-', $originalFieldset) . ".blade.php"));
    @unlink(base_path("resources/fieldsets/{$newFieldset}.yaml"));
    @unlink(base_path("resources/views/sets/" . str_replace('_', '-', $newFieldset) . ".blade.php"));
});

test('rename:bedrock-set fails when source set does not exist', function () {
    $this->artisan('rename:bedrock-set', [
        'group' => 'text_layout',
        'current_name' => 'nonexistent_set',
        'new_name' => 'New Name',
    ])->assertExitCode(Command::FAILURE);
});

