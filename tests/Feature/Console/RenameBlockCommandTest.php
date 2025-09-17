<?php

use App\Support\Yaml\BlocksYaml;
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
        if (str_contains(strtolower($label), 'move this block to a different group')) {
            return false;
        }
        // Default to yes for other confirmations (e.g. rename confirmation).
        return true;
    });
});

beforeEach(function () {
    // Snapshot YAMLs so we can restore after each test.
    $this->blocksYamlPath = base_path('resources/fieldsets/blocks.yaml');

    $this->originalBlocksYaml = is_file($this->blocksYamlPath)
        ? file_get_contents($this->blocksYamlPath)
        : '';
});

afterEach(function () {
    // Restore YAMLs
    if ($this->originalBlocksYaml !== '') {
        file_put_contents($this->blocksYamlPath, $this->originalBlocksYaml);
    }

    // Remove any scaffolding files created by tests.
    $globPaths = [
        base_path('resources/fieldsets/scaffold_test_*.yaml'),
        base_path('resources/fieldsets/scaffold_renamed_*.yaml'),
        base_path('resources/views/blocks/scaffold-test-*.blade.php'),
        base_path('resources/views/blocks/scaffold-renamed-*.blade.php'),
        base_path('content/collections/pages/test-page*.md'),
    ];

    foreach ($globPaths as $pattern) {
        foreach (glob($pattern) ?: [] as $file) {
            @unlink($file);
        }
    }
});

test('rename:bedrock-block renames files and updates blocks.yaml', function () {
    $group = 'messaging';
    $originalName = 'Scaffold Test Block ' . Str::random(6);
    $newName = 'Scaffold Renamed Block ' . Str::random(6);

    $locale = StatamicConfig::getShortLocale();
    $originalFieldset = Str::slug($originalName, '_', $locale);
    $originalView = Str::slug($originalName, '-', $locale);
    $newFieldset = Str::slug($newName, '_', $locale);
    $newView = Str::slug($newName, '-', $locale);

    // First create a block
    $this->artisan('make:bedrock-block', [
        'group' => $group,
        'name' => $originalName,
        '--instructions' => 'Test instructions',
        '--force' => true,
    ])->assertExitCode(Command::SUCCESS);

    $originalFieldsetPath = base_path("resources/fieldsets/{$originalFieldset}.yaml");
    $originalViewPath = base_path("resources/views/blocks/{$originalView}.blade.php");
    $newFieldsetPath = base_path("resources/fieldsets/{$newFieldset}.yaml");
    $newViewPath = base_path("resources/views/blocks/{$newView}.blade.php");

    // Verify original files exist
    expect(is_file($originalFieldsetPath))->toBeTrue();
    expect(is_file($originalViewPath))->toBeTrue();

    // Now rename the block
    $this->artisan('rename:bedrock-block', [
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

    // Verify blocks.yaml is updated using BlocksYaml class
    $blocks = app(BlocksYaml::class);
    $sets = $blocks->sets($group);

    expect(isset($sets[$originalFieldset]))->toBeFalse();
    expect(isset($sets[$newFieldset]))->toBeTrue();
    expect($sets[$newFieldset])->toBe($newName);

    // Fieldset title should be updated
    $data = \Statamic\Facades\YAML::file($newFieldsetPath)->parse() ?? [];
    expect($data['title'] ?? null)->toBe($newName);
});

test('rename:bedrock-block updates content entries', function () {
    $group = 'messaging';
    $originalName = 'Scaffold Test Block ' . Str::random(6);
    $newName = 'Scaffold Renamed Block ' . Str::random(6);

    $locale = StatamicConfig::getShortLocale();
    $originalFieldset = Str::slug($originalName, '_', $locale);
    $newFieldset = Str::slug($newName, '_', $locale);

    // First create a block
    $this->artisan('make:bedrock-block', [
        'group' => $group,
        'name' => $originalName,
        '--instructions' => 'Test instructions',
        '--force' => true,
    ])->assertExitCode(Command::SUCCESS);

    // Create a page entry that uses the block
    /** @var \Statamic\Entries\Entry $entry */
    $entry = Entry::make();
    $entry->collection('pages');
    $entry->id('test-page-' . Str::random(6));
    $entry->slug('test-page');
    $entry->data([
        'title' => 'Test Page',
        'blocks' => [['type' => $originalFieldset, 'enabled' => true]],
    ]);
    $entry->save();
    $entryId = $entry->id();

    // Now rename the block
    $this->artisan('rename:bedrock-block', [
        'group' => $group,
        'current_name' => $originalFieldset,
        'new_name' => $newName,
        '--force' => true,
    ])->assertExitCode(Command::SUCCESS);

    // Verify entry is updated with new block type
    /** @var \Statamic\Entries\Entry|null $updated */
    $updated = Entry::find($entryId);
    expect($updated)->not->toBeNull();

    $blocks = (array) $updated->data()->get('blocks');
    $hasOldBlock = collect($blocks)->contains(
        fn($i) => is_array($i) && ($i['type'] ?? null) === $originalFieldset
    );
    $hasNewBlock = collect($blocks)->contains(
        fn($i) => is_array($i) && ($i['type'] ?? null) === $newFieldset
    );

    expect($hasOldBlock)->toBeFalse();
    expect($hasNewBlock)->toBeTrue();
});

test('rename:bedrock-block fails when target files exist without --force', function () {
    $group = 'messaging';
    $originalName = 'Scaffold Test Block ' . Str::random(6);
    $newName = 'Scaffold Renamed Block ' . Str::random(6);

    $locale = StatamicConfig::getShortLocale();
    $originalFieldset = Str::slug($originalName, '_', $locale);
    $newFieldset = Str::slug($newName, '_', $locale);

    // Create original block
    $this->artisan('make:bedrock-block', [
        'group' => $group,
        'name' => $originalName,
        '--instructions' => 'Test instructions',
        '--force' => true,
    ])->assertExitCode(Command::SUCCESS);

    // Create another block with the target name
    $this->artisan('make:bedrock-block', [
        'group' => $group,
        'name' => $newName,
        '--instructions' => 'Test instructions',
        '--force' => true,
    ])->assertExitCode(Command::SUCCESS);

    // Rename should fail without --force
    $this->artisan('rename:bedrock-block', [
        'group' => $group,
        'current_name' => $originalFieldset,
        'new_name' => $newName,
    ])->assertExitCode(Command::FAILURE);

    // Cleanup created files
    @unlink(base_path("resources/fieldsets/{$originalFieldset}.yaml"));
    @unlink(base_path("resources/views/blocks/{$originalFieldset}.blade.php"));
    @unlink(base_path("resources/fieldsets/{$newFieldset}.yaml"));
    @unlink(base_path("resources/views/blocks/{$newFieldset}.blade.php"));
});

test('rename:bedrock-block fails when source block does not exist', function () {
    $this->artisan('rename:bedrock-block', [
        'group' => 'messaging',
        'current_name' => 'nonexistent_block',
        'new_name' => 'New Name',
    ])->assertExitCode(Command::FAILURE);
});
