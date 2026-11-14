<?php

declare(strict_types=1);

use App\Actions\StarterKit\FindMissingPartials;
use Illuminate\Support\Facades\File;

function partialsExportPath(): string
{
    return storage_path('framework/testing/find-missing-partials');
}

function exportView(string $path, string $contents = ''): void
{
    $file = partialsExportPath()."/resources/views/{$path}";

    File::ensureDirectoryExists(dirname($file));
    File::put($file, $contents);
}

afterEach(function (): void {
    File::deleteDirectory(partialsExportPath());
});

test('no partial is reported when the included partial was exported', function (string $include, string $partialPath): void {
    exportView('page.antlers.html', $include);
    exportView($partialPath);

    expect((new FindMissingPartials)->handle(partialsExportPath()))->toBeEmpty();
})->with([
    'partial tag' => '{{ partial:hero }}',
    'partial component' => '<s:partial:hero />',
    'src parameter' => '{{ partial src="hero" }}',
    'single-quoted src parameter' => "{{ partial src='hero' }}",
    'include alias' => '{{ include:hero }}',
])->with([
    'name' => 'hero.antlers.html',
    'underscored name' => '_hero.antlers.html',
    'partials folder' => 'partials/hero.antlers.html',
    'underscored name in partials folder' => 'partials/_hero.antlers.html',
]);

test('a partial name with dots is found in the nested folder', function (): void {
    exportView('page.antlers.html', '{{ partial:components.ui.button }}');
    exportView('components/ui/button.antlers.html');

    expect((new FindMissingPartials)->handle(partialsExportPath()))->toBeEmpty();
});

test('a partial that was not exported is reported once with the view that includes it', function (): void {
    exportView('blocks/hero.antlers.html', '<s:partial:components/missing>{{ partial:components/missing }}</s:partial:components/missing>');

    expect((new FindMissingPartials)->handle(partialsExportPath()))
        ->toBe(['resources/views/blocks/hero.antlers.html includes the partial components/missing, which was not exported']);
});

test('a partial name built from a variable is not reported', function (string $include): void {
    exportView('page.antlers.html', $include);

    expect((new FindMissingPartials)->handle(partialsExportPath()))->toBeEmpty();
})->with([
    'interpolated name' => '{{ partial:blocks/{type} }}',
    'interpolated src parameter' => '<s:partial src="blocks/{type}" />',
    'variable src parameter' => '{{ partial :src="handle" }}',
]);

test('a partial in an Antlers comment is not reported', function (): void {
    exportView('page.antlers.html', '{{# {{ partial:components/missing }} #}}');

    expect((new FindMissingPartials)->handle(partialsExportPath()))->toBeEmpty();
});
