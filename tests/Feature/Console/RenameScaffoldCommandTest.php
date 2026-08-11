<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Statamic\Facades\YAML;
use Tests\Feature\Console\Support\ScaffoldFixture;
use Tests\Feature\Console\Support\Scratch;
use Tests\Feature\Console\Support\TestEntry;

beforeEach(function (): void {
    Scratch::setUpScaffoldWorkspace();
    Scratch::isolateContentTree();
});

afterEach(function (): void {
    Scratch::delete();
});

test('rename moves the files and updates the parent fieldset', function (ScaffoldFixture $scaffold): void {
    [, $fieldset, $view] = $scaffold->create();
    [$newFieldset, $newView] = ScaffoldFixture::handles($newName = 'Scaffold Renamed '.Str::random(6));

    expect($scaffold->fieldsetPath($fieldset))->toBeFile()
        ->and($scaffold->viewPath($view))->toBeFile();

    $this->artisan($scaffold->command('rename'), [
        'group' => $scaffold->group(),
        'current_name' => $fieldset,
        'new_name' => $newName,
        '--force' => true,
    ])->assertSuccessful();

    expect($scaffold->fieldsetPath($fieldset))->not->toBeFile()
        ->and($scaffold->viewPath($view))->not->toBeFile()
        ->and($scaffold->fieldsetPath($newFieldset))->toBeFile()
        ->and($scaffold->viewPath($newView))->toBeFile();

    $declared = $scaffold->declaredSets();

    expect($declared)->not->toHaveKey($fieldset)
        ->and($declared[$newFieldset]['display'])->toBe($newName)
        ->and(YAML::file($scaffold->fieldsetPath($newFieldset))->parse()['title'])->toBe($newName);
})->with('scaffolds');

test('rename updates the entry usages', function (ScaffoldFixture $scaffold): void {
    [, $fieldset] = $scaffold->create();
    [$newFieldset] = ScaffoldFixture::handles($newName = 'Scaffold Renamed '.Str::random(6));

    $entry = TestEntry::create($scaffold->collection(), [
        'title' => 'Test Entry',
        $scaffold->entryField() => $scaffold->entryContent($fieldset),
    ]);

    $this->artisan($scaffold->command('rename'), [
        'group' => $scaffold->group(),
        'current_name' => $fieldset,
        'new_name' => $newName,
        '--force' => true,
    ])->assertSuccessful();

    $updated = TestEntry::fresh($entry->id());

    expect($updated)->not->toBeNull()
        ->and($scaffold->usedHandles($updated))->not->toContain($fieldset)
        ->and($scaffold->usedHandles($updated))->toContain($newFieldset);
})->with('scaffolds');

test('rename fails when the target files exist without --force', function (ScaffoldFixture $scaffold): void {
    [$name, $fieldset] = $scaffold->create();
    [$newName] = $scaffold->create();

    $this->artisan($scaffold->command('rename'), [
        'group' => $scaffold->group(),
        'current_name' => $fieldset,
        'new_name' => $newName,
    ])
        ->expectsConfirmation(
            "Rename {$scaffold->noun()} '{$name}' to '{$newName}'? This will update all content entries.",
            'yes'
        )
        ->assertFailed();
})->with('scaffolds');

test('rename fails when the source does not exist', function (ScaffoldFixture $scaffold): void {
    $this->artisan($scaffold->command('rename'), [
        'group' => $scaffold->group(),
        'current_name' => 'does_not_exist',
        'new_name' => 'New Name',
    ])
        ->expectsOutputToContain("The 'does_not_exist' {$scaffold->noun()} was not found")
        ->assertFailed();
})->with('scaffolds');

test('rename fails when the parent fieldset declares no groups', function (ScaffoldFixture $scaffold): void {
    File::put($scaffold->parentFieldsetPath(), YAML::dump($scaffold->parentFieldsetWithoutGroups()));

    $this->artisan($scaffold->command('rename'), [
        'group' => $scaffold->group(),
        'current_name' => 'whatever',
        'new_name' => 'New Name',
        '--force' => true,
    ])
        ->expectsOutputToContain('No groups found')
        ->assertFailed();
})->with('scaffolds');

test('rename with an unknown group fails with an error', function (ScaffoldFixture $scaffold): void {
    $this->artisan($scaffold->command('rename'), [
        'group' => 'does_not_exist',
        'current_name' => 'whatever',
        'new_name' => 'New Name',
        '--force' => true,
    ])
        ->expectsOutputToContain("Group 'does_not_exist' not found")
        ->assertFailed();
})->with('scaffolds');

test('rename reports an empty group instead of prompting', function (ScaffoldFixture $scaffold): void {
    File::put($scaffold->parentFieldsetPath(), YAML::dump($scaffold->parentFieldsetWithEmptyGroup()));

    $this->artisan($scaffold->command('rename'), [
        'group' => $scaffold->group(),
        '--force' => true,
    ])
        ->expectsOutputToContain("The '{$scaffold->groupDisplay()}' group has no {$scaffold->noun()}s.")
        ->assertSuccessful();
})->with('scaffolds');

test('rename notes the missing source files and still renames the declaration', function (ScaffoldFixture $scaffold): void {
    [, $fieldset, $view] = $scaffold->create();
    [$newFieldset] = ScaffoldFixture::handles($newName = 'Scaffold Renamed '.Str::random(6));

    File::delete([$scaffold->fieldsetPath($fieldset), $scaffold->viewPath($view)]);

    $this->artisan($scaffold->command('rename'), [
        'group' => $scaffold->group(),
        'current_name' => $fieldset,
        'new_name' => $newName,
        '--force' => true,
    ])
        ->expectsOutputToContain('Note: Fieldset file not found')
        ->expectsOutputToContain('Note: View file not found')
        ->assertSuccessful();

    $declared = $scaffold->declaredSets();

    expect($declared)->not->toHaveKey($fieldset)
        ->and($declared)->toHaveKey($newFieldset);
})->with('scaffolds');

test('rename with --force overwrites the files of an existing set', function (ScaffoldFixture $scaffold): void {
    [, $fieldset] = $scaffold->create();
    [$targetName, $targetFieldset, $targetView] = $scaffold->create();

    File::put($scaffold->viewPath($targetView), 'target view');

    $this->artisan($scaffold->command('rename'), [
        'group' => $scaffold->group(),
        'current_name' => $fieldset,
        'new_name' => $targetName,
        '--force' => true,
    ])->assertSuccessful();

    expect($scaffold->declaredSets())->not->toHaveKey($fieldset)
        ->and($scaffold->declaredSets())->toHaveKey($targetFieldset)
        ->and(File::get($scaffold->viewPath($targetView)))->not->toBe('target view');
})->with('scaffolds');
