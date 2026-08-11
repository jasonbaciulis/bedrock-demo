<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Statamic\Facades\YAML;
use Tests\Feature\Console\Support\ScaffoldFixture;
use Tests\Feature\Console\Support\Scratch;

beforeEach(function (): void {
    Scratch::setUpScaffoldWorkspace();
});

afterEach(function (): void {
    Scratch::delete();
});

// A sectioned fieldset drops the top-level 'fields' key the registry reads.
test('a scaffold command reports a parent fieldset that uses sections', function (ScaffoldFixture $scaffold): void {
    File::put($scaffold->parentFieldsetPath(), YAML::dump([
        'title' => 'Parent',
        'sections' => [
            'main' => ['fields' => [['handle' => $scaffold->entryField(), 'field' => ['type' => 'text']]]],
        ],
    ]));

    $this->artisan($scaffold->command('delete'), [
        'group' => $scaffold->group(),
        $scaffold->noun() => 'whatever',
        '--force' => true,
    ])->run();
})->with('scaffolds')->throws(RuntimeException::class, "missing 'fields'");

test('a scaffold command reports a parent fieldset with no entry field', function (ScaffoldFixture $scaffold): void {
    File::put($scaffold->parentFieldsetPath(), YAML::dump($scaffold->parentFieldsetWithoutEntryField()));

    $this->artisan($scaffold->command('delete'), [
        'group' => $scaffold->group(),
        $scaffold->noun() => 'whatever',
        '--force' => true,
    ])->run();
})->with('scaffolds')->throws(RuntimeException::class, 'not found in');
