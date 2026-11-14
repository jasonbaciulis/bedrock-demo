<?php

declare(strict_types=1);

use App\Actions\StarterKit\FindMissingExportPaths;
use Illuminate\Support\Facades\File;
use Statamic\Facades\YAML;

function exportPathsKitPath(): string
{
    return storage_path('framework/testing/find-missing-export-paths');
}

/**
 * @param  list<string>  $exportPaths
 */
function kitWithExportPaths(array $exportPaths): void
{
    File::ensureDirectoryExists(exportPathsKitPath().'/export');
    File::put(exportPathsKitPath().'/starter-kit.yaml', YAML::dump(['export_paths' => $exportPaths]));
}

function exportedKitFile(string $path): void
{
    $file = exportPathsKitPath()."/export/{$path}";

    File::ensureDirectoryExists(dirname($file));
    File::put($file, '');
}

afterEach(function (): void {
    File::deleteDirectory(exportPathsKitPath());
});

test('no file is reported when export_paths lists the file or its folder', function (): void {
    kitWithExportPaths(['.blade.format.json', 'resources/forms/contact.yaml', 'resources/views/components/ui']);
    exportedKitFile('.blade.format.json');
    exportedKitFile('resources/forms/contact.yaml');
    exportedKitFile('resources/views/components/ui/button.antlers.html');

    expect((new FindMissingExportPaths)->handle(exportPathsKitPath()))->toBeEmpty();
});

test('an exported file that export_paths does not list is reported', function (): void {
    kitWithExportPaths(['resources/views/components/ui']);
    exportedKitFile('resources/views/components/ui-legacy/button.antlers.html');
    exportedKitFile('resources/views/partials/nav-header-mob.antlers.html');

    expect((new FindMissingExportPaths)->handle(exportPathsKitPath()))->toBe([
        'resources/views/components/ui-legacy/button.antlers.html is in the kit, but not in export_paths, so the installer skips it',
        'resources/views/partials/nav-header-mob.antlers.html is in the kit, but not in export_paths, so the installer skips it',
    ]);
});
