<?php

declare(strict_types=1);

use Illuminate\Support\Str;
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

test('make prompts for the group, the name and the instructions', function (ScaffoldFixture $scaffold): void {
    [$fieldset, $view] = ScaffoldFixture::handles($name = 'Scaffold Test '.Str::random(6));

    $this->artisan($scaffold->command('make'))
        ->expectsQuestion("Which group should this {$scaffold->noun()} belong to?", $scaffold->group())
        ->expectsQuestion("What should the {$scaffold->noun()} be named?", $name)
        ->expectsQuestion('What should be the instructions?', 'Prompted instructions')
        ->assertSuccessful();

    expect($scaffold->fieldsetPath($fieldset))->toBeFile()
        ->and($scaffold->viewPath($view))->toBeFile()
        ->and($scaffold->declaredSets()[$fieldset]['instructions'])->toBe('Prompted instructions');
})->with('scaffolds');

test('delete prompts for the group and the set, then confirms', function (ScaffoldFixture $scaffold): void {
    [$name, $fieldset] = $scaffold->create();

    $this->artisan($scaffold->command('delete'))
        ->expectsQuestion("Which group contains the {$scaffold->noun()}?", $scaffold->group())
        ->expectsQuestion("Which {$scaffold->noun()} would you like to delete?", $fieldset)
        ->expectsConfirmation("Delete '{$name}' from '{$scaffold->groupDisplay()}' group?", 'yes')
        ->assertSuccessful();

    expect($scaffold->declaredSets())->not->toHaveKey($fieldset);
})->with('scaffolds');

test('delete keeps everything when the confirmation is declined', function (ScaffoldFixture $scaffold): void {
    [$name, $fieldset, $view] = $scaffold->create();

    $this->artisan($scaffold->command('delete'), [
        'group' => $scaffold->group(),
        $scaffold->noun() => $fieldset,
    ])
        ->expectsConfirmation("Delete '{$name}' from '{$scaffold->groupDisplay()}' group?", 'no')
        ->expectsOutputToContain('Deletion aborted.')
        ->assertSuccessful();

    expect($scaffold->declaredSets())->toHaveKey($fieldset)
        ->and($scaffold->fieldsetPath($fieldset))->toBeFile()
        ->and($scaffold->viewPath($view))->toBeFile();
})->with('scaffolds');

test('delete warns when entries use the set', function (ScaffoldFixture $scaffold): void {
    [$name, $fieldset] = $scaffold->create();

    TestEntry::create($scaffold->collection(), [
        'title' => 'Test Entry',
        $scaffold->entryField() => $scaffold->entryContent($fieldset),
    ]);

    $this->artisan($scaffold->command('delete'), [
        'group' => $scaffold->group(),
        $scaffold->noun() => $fieldset,
    ])
        ->expectsConfirmation("Delete '{$name}' from '{$scaffold->groupDisplay()}' group?", 'yes')
        ->expectsOutputToContain('is used in 1 entry')
        ->expectsOutputToContain('Removed from 1 entry.')
        ->assertSuccessful();
})->with('scaffolds');

test('rename prompts for the group, the set, the new name and the target group', function (ScaffoldFixture $scaffold): void {
    [$name, $fieldset] = $scaffold->create();
    [$newFieldset] = ScaffoldFixture::handles($newName = 'Scaffold Renamed '.Str::random(6));

    $this->artisan($scaffold->command('rename'))
        ->expectsQuestion("Which group contains the {$scaffold->noun()}?", $scaffold->group())
        ->expectsQuestion("Which {$scaffold->noun()} would you like to rename?", $fieldset)
        ->expectsQuestion("What should the new {$scaffold->noun()} name be?", $newName)
        ->expectsConfirmation("Move this {$scaffold->noun()} to a different group?", 'no')
        ->expectsConfirmation(
            "Rename {$scaffold->noun()} '{$name}' to '{$newName}'? This will update all content entries.",
            'yes'
        )
        ->assertSuccessful();

    expect($scaffold->declaredSets())->toHaveKey($newFieldset)
        ->and($scaffold->declaredSets())->not->toHaveKey($fieldset);
})->with('scaffolds');

test('rename moves the set into the group picked at the prompt', function (ScaffoldFixture $scaffold): void {
    [$name, $fieldset] = $scaffold->create();
    [$newFieldset] = ScaffoldFixture::handles($newName = 'Scaffold Renamed '.Str::random(6));

    $this->artisan($scaffold->command('rename'))
        ->expectsQuestion("Which group contains the {$scaffold->noun()}?", $scaffold->group())
        ->expectsQuestion("Which {$scaffold->noun()} would you like to rename?", $fieldset)
        ->expectsQuestion("What should the new {$scaffold->noun()} name be?", $newName)
        ->expectsConfirmation("Move this {$scaffold->noun()} to a different group?", 'yes')
        ->expectsQuestion('Select the new group', $scaffold->otherGroup())
        ->expectsConfirmation(
            "Rename {$scaffold->noun()} '{$name}' to '{$newName}'? This will update all content entries.",
            'yes'
        )
        ->assertSuccessful();

    expect($scaffold->declaredSets())->not->toHaveKey($newFieldset)
        ->and($scaffold->declaredSetsIn($scaffold->otherGroup()))->toHaveKey($newFieldset);
})->with('scaffolds');

test('rename keeps everything when the confirmation is declined', function (ScaffoldFixture $scaffold): void {
    [$name, $fieldset] = $scaffold->create();
    $newName = 'Scaffold Renamed '.Str::random(6);

    $this->artisan($scaffold->command('rename'), [
        'group' => $scaffold->group(),
        'current_name' => $fieldset,
        'new_name' => $newName,
    ])
        ->expectsConfirmation(
            "Rename {$scaffold->noun()} '{$name}' to '{$newName}'? This will update all content entries.",
            'no'
        )
        ->expectsOutputToContain('Rename aborted.')
        ->assertSuccessful();

    expect($scaffold->declaredSets())->toHaveKey($fieldset);
})->with('scaffolds');
