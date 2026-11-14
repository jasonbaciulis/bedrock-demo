<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\StarterKit\FindMissingExportPaths;
use App\Actions\StarterKit\FindMissingFieldsets;
use App\Actions\StarterKit\FindMissingPartials;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Statamic\Facades\Path;
use Statamic\StarterKits\Exporter;
use Symfony\Component\Finder\Finder;

#[Signature('bedrock:export-starter-kit {path=../bedrock : The starter kit repository}')]
#[Description('Export the starter kit and check that the export is complete')]
final class ExportStarterKitCommand extends Command
{
    /** The kit repository keeps its own Git history, workflows and Claude commands, which `Exporter::clear()` would delete except for `.git`. */
    private const array KIT_OWNED_PATHS = ['.git', '.claude', '.github'];

    /** These tests stay in this project, because this command is not exported. */
    private const string EXPORT_COMMAND_TESTS = 'tests/Feature/StarterKitExport';

    /**
     * Statamic installs `dependencies_dev` without `--with-all-dependencies`, which Pest 5 and PHPUnit 13 need,
     * so package/StarterKitPostInstall.php installs these with the versions from composer.json.
     */
    private const array HOOK_DEV_DEPENDENCIES = [
        'cboxdk/statamic-mcp',
        'driftingly/rector-laravel',
        'jasonbaciulis/bedrock-commands',
        'larastan/larastan',
        'laravel/boost',
        'laravel/pao',
        'pestphp/pest',
        'pestphp/pest-plugin-browser',
        'pestphp/pest-plugin-laravel',
        'pestphp/pest-plugin-phpstan',
        'pestphp/pest-plugin-rector',
        'pestphp/pest-plugin-type-coverage',
        'phpstan/phpstan',
        'phpunit/phpunit',
        'rector/rector',
        'roave/security-advisories',
    ];

    public function handle(
        FindMissingExportPaths $findMissingExportPaths,
        FindMissingPartials $findMissingPartials,
        FindMissingFieldsets $findMissingFieldsets,
    ): int {
        $kitPath = $this->kitPath();

        /** @var array<string, string> $requireDev */
        $requireDev = Arr::array(File::json(base_path('composer.json')), 'require-dev');

        // This check runs first, so that a failure leaves the kit repository as it was.
        if (! $this->hookDevDependenciesAreInComposer($requireDev)) {
            return self::FAILURE;
        }

        $this->components->task('Remove the previous export', fn () => $this->cleanKitDirectory($kitPath));
        $this->components->task('Export the starter kit', fn () => new Exporter($kitPath)->export());
        $this->components->task('Remove the tests of the export command', fn () => File::deleteDirectory("{$kitPath}/export/".self::EXPORT_COMMAND_TESTS));
        $this->components->task('Write the dev dependency versions', fn () => $this->writeHookDevDependencies($kitPath, $requireDev));

        return $this->exportIsComplete($kitPath, $findMissingExportPaths, $findMissingPartials, $findMissingFieldsets) ? self::SUCCESS : self::FAILURE;
    }

    private function kitPath(): string
    {
        $path = $this->argument('path');

        return Path::isAbsolute($path) ? $path : Path::makeFull($path);
    }

    /**
     * @param  array<string, string>  $requireDev
     */
    private function hookDevDependenciesAreInComposer(array $requireDev): bool
    {
        $missingDependencies = array_values(array_diff(self::HOOK_DEV_DEPENDENCIES, array_keys($requireDev)));

        return $this->checkPasses(
            array_map(fn (string $dependency): string => "{$dependency} is not in require-dev", $missingDependencies),
            success: 'All dev dependencies of the post-install hook are in composer.json.',
            fix: 'Add them to require-dev, or remove them from HOOK_DEV_DEPENDENCIES in ExportStarterKitCommand.',
        );
    }

    private function cleanKitDirectory(string $kitPath): void
    {
        $previousExport = Finder::create()
            ->in($kitPath)
            ->depth(0)
            ->ignoreDotFiles(false)
            ->notName(self::KIT_OWNED_PATHS);

        foreach ($previousExport as $file) {
            if ($file->isDir()) {
                File::deleteDirectory($file->getPathname());
            } else {
                File::delete($file->getPathname());
            }
        }
    }

    /**
     * package/StarterKitPostInstall.php reads this file from the kit root.
     *
     * @param  array<string, string>  $requireDev
     */
    private function writeHookDevDependencies(string $kitPath, array $requireDev): void
    {
        $versions = Arr::only($requireDev, self::HOOK_DEV_DEPENDENCIES);

        File::put("{$kitPath}/dev-dependencies.json", json_encode($versions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
    }

    /**
     * export_paths lists single files, so a new view or fieldset can be left out by mistake.
     */
    private function exportIsComplete(
        string $kitPath,
        FindMissingExportPaths $findMissingExportPaths,
        FindMissingPartials $findMissingPartials,
        FindMissingFieldsets $findMissingFieldsets,
    ): bool {
        $exportPath = "{$kitPath}/export";

        return $this->checkPasses(
            [
                ...$findMissingExportPaths->handle($kitPath),
                ...$findMissingPartials->handle($exportPath),
                ...$findMissingFieldsets->handle($exportPath),
            ],
            success: 'export_paths lists all exported files, and all partials and fieldsets were exported.',
            fix: 'Add the missing files to export_paths in package/starter-kit.yaml.',
        );
    }

    /**
     * @param  list<string>  $problems
     */
    private function checkPasses(array $problems, string $success, string $fix): bool
    {
        if ($problems === []) {
            $this->components->info($success);

            return true;
        }

        $this->components->bulletList($problems);
        $this->components->error($fix);

        return false;
    }
}
