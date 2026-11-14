<?php

declare(strict_types=1);

use App\Actions\StarterKit\FindMissingFieldsets;
use Illuminate\Support\Facades\File;

function fieldsetsExportPath(): string
{
    return storage_path('framework/testing/find-missing-fieldsets');
}

function exportYaml(string $path, string $contents = ''): void
{
    $file = fieldsetsExportPath()."/resources/{$path}";

    File::ensureDirectoryExists(dirname($file));
    File::put($file, $contents);
}

beforeEach(function (): void {
    File::ensureDirectoryExists(fieldsetsExportPath().'/resources/fieldsets');
    File::ensureDirectoryExists(fieldsetsExportPath().'/resources/blueprints');
});

afterEach(function (): void {
    File::deleteDirectory(fieldsetsExportPath());
});

test('no fieldset is reported when every referenced fieldset was exported', function (): void {
    exportYaml('blueprints/collections/pages/page.yaml', <<<'YAML'
        tabs:
          main:
            sections:
              -
                fields:
                  -
                    handle: title
                    field: common.text_plain
                  -
                    import: buttons
                  -
                    import: forms.contact
        YAML);
    exportYaml('fieldsets/common.yaml');
    exportYaml('fieldsets/buttons.yaml');
    exportYaml('fieldsets/forms/contact.yaml');

    expect((new FindMissingFieldsets)->handle(fieldsetsExportPath()))->toBeEmpty();
});

test('an imported fieldset that was not exported is reported', function (): void {
    exportYaml('fieldsets/hero.yaml', <<<'YAML'
        fields:
          -
            import: buttons
        YAML);

    expect((new FindMissingFieldsets)->handle(fieldsetsExportPath()))
        ->toBe(['resources/fieldsets/hero.yaml uses the fieldset buttons, which was not exported']);
});

test('the fieldset of a field reference is reported once when it was not exported', function (): void {
    exportYaml('blueprints/user.yaml', <<<'YAML'
        fields:
          -
            handle: name
            field: common.text_plain
          -
            handle: photo
            field: common.image
        YAML);

    expect((new FindMissingFieldsets)->handle(fieldsetsExportPath()))
        ->toBe(['resources/blueprints/user.yaml uses the fieldset common, which was not exported']);
});

test('an inline field config is not taken for a fieldset reference', function (): void {
    exportYaml('fieldsets/hero.yaml', <<<'YAML'
        fields:
          -
            handle: title
            field:
              type: text
        YAML);

    expect((new FindMissingFieldsets)->handle(fieldsetsExportPath()))->toBeEmpty();
});
