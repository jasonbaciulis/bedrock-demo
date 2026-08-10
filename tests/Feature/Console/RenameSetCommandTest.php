<?php

use App\Support\Yaml\ArticleYaml;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Laravel\Prompts\ConfirmPrompt;
use Laravel\Prompts\Prompt;
use Statamic\Facades\Config as StatamicConfig;
use Statamic\Facades\Entry;
use Statamic\Facades\YAML;

beforeAll(function (): void {
    // Always auto-confirm prompts in tests, except for optional group move.
    Prompt::fallbackWhen(true);
    ConfirmPrompt::fallbackUsing(
        fn (ConfirmPrompt $prompt): bool => ! Str::contains(strtolower($prompt->label()), 'move this set to a different group')
    );
});

beforeEach(function (): void {
    setUpBedrockScaffoldPaths();
});

afterEach(function (): void {
    tearDownBedrockScaffoldPaths();

    foreach (glob(base_path('content/collections/posts/test-post-*.md')) ?: [] as $file) {
        @unlink($file);
    }
});

test('rename:bedrock-set renames files and updates article.yaml', function (): void {
    $group = 'text_layout';
    $originalName = 'Scaffold Test Set '.Str::random(6);
    $newName = 'Scaffold Renamed Set '.Str::random(6);

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

    $originalFieldsetPath = config('statamic.bedrock.scaffold.fieldsets_path')."/{$originalFieldset}.yaml";
    $originalViewPath = config('statamic.bedrock.scaffold.sets_views_path')."/{$originalView}.antlers.html";
    $newFieldsetPath = config('statamic.bedrock.scaffold.fieldsets_path')."/{$newFieldset}.yaml";
    $newViewPath = config('statamic.bedrock.scaffold.sets_views_path')."/{$newView}.antlers.html";

    // Verify original files exist
    expect($originalFieldsetPath)->toBeFile();
    expect($originalViewPath)->toBeFile();

    // Now rename the set
    $this->artisan('rename:bedrock-set', [
        'group' => $group,
        'current_name' => $originalFieldset,
        'new_name' => $newName,
        '--force' => true,
    ])->assertExitCode(Command::SUCCESS);

    // Verify old files are gone and new files exist
    expect($originalFieldsetPath)->not->toBeFile();
    expect($originalViewPath)->not->toBeFile()
        ->and($newFieldsetPath)->toBeFile()
        ->and($newViewPath)->toBeFile();

    // Verify article.yaml is updated using ArticleYaml class
    $article = resolve(ArticleYaml::class);
    $sets = $article->sets($group);

    expect(isset($sets[$originalFieldset]))->toBeFalse()
        ->and(isset($sets[$newFieldset]))->toBeTrue()
        ->and($sets[$newFieldset])->toBe($newName);

    // Fieldset title should be updated
    $data = YAML::file($newFieldsetPath)->parse();
    expect($data['title'] ?? null)->toBe($newName);
});

test('rename:bedrock-set updates content entries', function (): void {
    $group = 'text_layout';
    $originalName = 'Scaffold Test Set '.Str::random(6);
    $newName = 'Scaffold Renamed Set '.Str::random(6);

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
    /** @var Statamic\Entries\Entry $entry */
    $entry = Entry::make();
    $entry->collection('posts');
    $entry->id($entryId = bedrockTestEntryId('test-post'));
    $entry->slug($entryId);
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

    // Now rename the set
    $this->artisan('rename:bedrock-set', [
        'group' => $group,
        'current_name' => $originalFieldset,
        'new_name' => $newName,
        '--force' => true,
    ])->assertExitCode(Command::SUCCESS);

    // Verify entry is updated with new set type
    /** @var Statamic\Entries\Entry|null $updated */
    $updated = Entry::find($entryId);
    expect($updated)->not->toBeNull();

    $article = (array) $updated->data()->get('article');
    $hasOldSet = collect($article)->contains(function ($node) use ($originalFieldset): bool {
        if (! is_array($node) || ($node['type'] ?? null) !== 'set') {
            return false;
        }

        return ($node['attrs']['values']['type'] ?? null) === $originalFieldset;
    });
    $hasNewSet = collect($article)->contains(function ($node) use ($newFieldset): bool {
        if (! is_array($node) || ($node['type'] ?? null) !== 'set') {
            return false;
        }

        return ($node['attrs']['values']['type'] ?? null) === $newFieldset;
    });

    expect($hasOldSet)->toBeFalse()
        ->and($hasNewSet)->toBeTrue();
});

test('rename:bedrock-set fails when target files exist without --force', function (): void {
    $group = 'text_layout';
    $originalName = 'Scaffold Test Set '.Str::random(6);
    $newName = 'Scaffold Renamed Set '.Str::random(6);

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
});

test('rename:bedrock-set fails when source set does not exist', function (): void {
    $this->artisan('rename:bedrock-set', [
        'group' => 'text_layout',
        'current_name' => 'nonexistent_set',
        'new_name' => 'New Name',
    ])->assertExitCode(Command::FAILURE);
});
