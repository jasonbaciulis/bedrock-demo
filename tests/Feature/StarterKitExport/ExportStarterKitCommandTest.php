<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

function kitPath(): string
{
    return storage_path('framework/testing/export-starter-kit');
}

function kitFile(string $path): void
{
    $file = kitPath()."/{$path}";

    File::ensureDirectoryExists(dirname($file));
    File::put($file, '');
}

afterEach(function (): void {
    File::deleteDirectory(kitPath());
});

test('the kit is exported and keeps its own Git history, workflows and Claude commands', function (): void {
    kitFile('.git/HEAD');
    kitFile('.github/workflows/release.yml');
    kitFile('.claude/commands/ship.md');
    kitFile('.removed-config');
    kitFile('export/resources/views/removed.antlers.html');

    $this->artisan('bedrock:export-starter-kit', ['path' => 'storage/framework/testing/export-starter-kit'])
        ->expectsOutputToContain('export_paths lists all exported files, and all partials and fieldsets were exported.')
        ->assertSuccessful()
        ->run();

    expect([
        kitPath().'/.git/HEAD',
        kitPath().'/.github/workflows/release.yml',
        kitPath().'/.claude/commands/ship.md',
        kitPath().'/export/resources/views/layout.antlers.html',
        kitPath().'/export/tests/TestCase.php',
    ])->each->toBeFile()
        ->and([
            kitPath().'/.removed-config',
            kitPath().'/export/resources/views/removed.antlers.html',
            kitPath().'/export/tests/Feature/StarterKitExport/ExportStarterKitCommandTest.php',
        ])->each->not->toBeFile();
});

test('the post-install hook gets the composer.json versions of its dev dependencies only', function (): void {
    kitFile('.git/HEAD');

    $this->artisan('bedrock:export-starter-kit', ['path' => kitPath()])->assertSuccessful()->run();

    $requireDev = File::json(base_path('composer.json'))['require-dev'];

    expect(File::json(kitPath().'/dev-dependencies.json'))
        ->toHaveKey('pestphp/pest', $requireDev['pestphp/pest'])
        ->not->toHaveKey('barryvdh/laravel-debugbar');
});

test('the kit is left as it was when a dev dependency of the post-install hook is not in composer.json', function (): void {
    kitFile('export/resources/views/layout.antlers.html');
    File::partialMock()
        ->shouldReceive('json')
        ->once()
        ->with(base_path('composer.json'))
        ->andReturn(['require-dev' => []]);

    $this->artisan('bedrock:export-starter-kit', ['path' => kitPath()])
        ->expectsOutputToContain('pestphp/pest is not in require-dev')
        ->assertFailed()
        ->run();

    expect(kitPath().'/export/resources/views/layout.antlers.html')->toBeFile();
});
