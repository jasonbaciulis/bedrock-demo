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
});

afterEach(function (): void {
    Scratch::delete();
    TestEntry::deleteAll();
});

test('make creates the files and declares the set in the parent fieldset', function (ScaffoldFixture $scaffold): void {
    [$name, $fieldset, $view] = $scaffold->create();

    expect($scaffold->fieldsetPath($fieldset))->toBeFile()
        ->and($scaffold->viewPath($view))->toBeFile();

    $declared = $scaffold->declaredSets()[$fieldset] ?? [];

    expect($declared)->not->toBeEmpty()
        ->and($declared['display'] ?? null)->toBe($name)
        ->and($declared['instructions'] ?? null)->toBe('Test instructions')
        ->and($declared['fields'][0]['import'] ?? null)->toBe($fieldset);
})->with('scaffolds');

test('make without --force fails when the files already exist', function (ScaffoldFixture $scaffold): void {
    [$name] = $scaffold->create();

    $this->artisan($scaffold->command('make'), [
        'group' => $scaffold->group(),
        'name' => $name,
        '--instructions' => 'Test instructions',
    ])->assertFailed();
})->with('scaffolds');

test('make fails when the parent fieldset declares no groups', function (ScaffoldFixture $scaffold): void {
    File::put($scaffold->parentFieldsetPath(), YAML::dump([
        'title' => 'Parent',
        'fields' => [
            [
                'handle' => $scaffold->entryField(),
                'field' => ['type' => $scaffold->fieldType(), 'sets' => []],
            ],
        ],
    ]));

    $this->artisan($scaffold->command('make'), [
        'group' => $scaffold->group(),
        'name' => 'Scaffold Test '.Str::random(6),
        '--instructions' => 'Test instructions',
        '--force' => true,
    ])
        ->expectsOutputToContain('No groups found')
        ->assertFailed();
})->with('scaffolds');

test('make with an unknown group fails before it creates files', function (ScaffoldFixture $scaffold): void {
    [$fieldset, $view] = ScaffoldFixture::handles($name = 'Scaffold Test '.Str::random(6));

    $this->artisan($scaffold->command('make'), [
        'group' => 'does_not_exist',
        'name' => $name,
        '--instructions' => 'Test instructions',
        '--force' => true,
    ])
        ->expectsOutputToContain("Group 'does_not_exist' not found")
        ->assertFailed();

    expect($scaffold->fieldsetPath($fieldset))->not->toBeFile()
        ->and($scaffold->viewPath($view))->not->toBeFile();
})->with('scaffolds');

test('delete removes the declaration, the files and the entry usages', function (ScaffoldFixture $scaffold): void {
    [$name, $fieldset, $view] = $scaffold->create();

    $entry = TestEntry::create($scaffold->collection(), [
        'title' => 'Test Entry',
        $scaffold->entryField() => $scaffold->entryContent($fieldset),
    ]);

    $this->artisan($scaffold->command('delete'), [
        'group' => $scaffold->group(),
        $scaffold->noun() => $fieldset,
    ])
        ->expectsConfirmation("Delete '{$name}' from '{$scaffold->groupDisplay()}' group?", 'yes')
        ->assertSuccessful();

    $updated = TestEntry::fresh($entry->id());

    expect($updated)->not->toBeNull()
        ->and($scaffold->usedHandles($updated))->not->toContain($fieldset)
        ->and($scaffold->declaredSets())->not->toHaveKey($fieldset)
        ->and($scaffold->fieldsetPath($fieldset))->not->toBeFile()
        ->and($scaffold->viewPath($view))->not->toBeFile();
})->with('scaffolds');

test('delete with --keep-files removes the declaration but keeps the files', function (ScaffoldFixture $scaffold): void {
    [$name, $fieldset, $view] = $scaffold->create();

    $this->artisan($scaffold->command('delete'), [
        'group' => $scaffold->group(),
        $scaffold->noun() => $fieldset,
        '--keep-files' => true,
    ])
        ->expectsConfirmation("Delete '{$name}' from '{$scaffold->groupDisplay()}' group?", 'yes')
        ->assertSuccessful();

    expect($scaffold->declaredSets())->not->toHaveKey($fieldset)
        ->and($scaffold->fieldsetPath($fieldset))->toBeFile()
        ->and($scaffold->viewPath($view))->toBeFile();
})->with('scaffolds');

test('delete with --force skips the confirmation prompt', function (ScaffoldFixture $scaffold): void {
    [, $fieldset] = $scaffold->create();

    // A shown prompt would hit the output mock without an expectation and fail the test.
    $this->artisan($scaffold->command('delete'), [
        'group' => $scaffold->group(),
        $scaffold->noun() => $fieldset,
        '--force' => true,
    ])->assertSuccessful();

    expect($scaffold->declaredSets())->not->toHaveKey($fieldset);
})->with('scaffolds');

test('delete with an unknown group fails with an error', function (ScaffoldFixture $scaffold): void {
    $this->artisan($scaffold->command('delete'), [
        'group' => 'does_not_exist',
        $scaffold->noun() => 'whatever',
        '--force' => true,
    ])
        ->expectsOutputToContain("Group 'does_not_exist' not found")
        ->assertFailed();
})->with('scaffolds');
