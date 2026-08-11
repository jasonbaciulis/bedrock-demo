<?php

declare(strict_types=1);

namespace App\Console\Commands\Scaffold\Support;

use App\Console\Commands\Scaffold\Contracts\ScaffoldTarget;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use RuntimeException;
use Statamic\Facades\Config;
use Statamic\Facades\YAML;

/**
 * Owns the fieldset/view file conventions for a scaffold target: slugs, paths,
 * and the create/delete/move operations on both files.
 */
final readonly class FieldsetFiles
{
    public function __construct(
        private Filesystem $files,
        private ScaffoldTarget $target
    ) {}

    /**
     * @throws RuntimeException When a file exists and $force is false
     */
    public function assertWritable(string $fieldset, string $view, bool $force): void
    {
        foreach ([$this->fieldsetPathFor($fieldset), $this->viewPathFor($view)] as $path) {
            throw_if($this->files->exists($path) && ! $force, RuntimeException::class, "File exists: {$path} (use --force to overwrite)");
        }
    }

    public function createFromStub(string $stub, string $targetPath, string $name): void
    {
        $contents = $this->files->get(app_path("Console/Commands/Scaffold/stubs/{$stub}"));

        $this->files->put($targetPath, Str::replace('{{ name }}', $name, $contents));
    }

    /**
     * @throws RuntimeException When files are missing and $force is false
     */
    public function deleteFor(string $fieldset, bool $force): void
    {
        $view = $this->viewSlugFromFieldsetHandle($fieldset);

        [$existing, $missing] = collect([$this->fieldsetPathFor($fieldset), $this->viewPathFor($view)])
            ->partition(fn (string $path): bool => $this->files->exists($path));

        $existing->each(fn (string $path) => $this->files->delete($path));

        if ($missing->isNotEmpty() && ! $force) {
            $list = $missing->implode("\n - ");
            throw new RuntimeException(
                "Some files were not found to delete:\n - {$list}\n(Use --force to ignore.)"
            );
        }
    }

    /**
     * Move/rename fieldset and view files, replacing destination files that already exist.
     *
     * @return list<string> Notes about source files that were missing
     */
    public function moveFor(
        string $currentHandle,
        string $originalView,
        string $newFieldset,
        string $newView
    ): array {
        return collect([
            $this->moveReplacing($this->fieldsetPathFor($currentHandle), $this->fieldsetPathFor($newFieldset), 'Fieldset'),
            $this->moveReplacing($this->viewPathFor($originalView), $this->viewPathFor($newView), 'View'),
        ])->filter()->values()->all();
    }

    public function updateFieldsetTitle(string $fieldsetHandle, string $newTitle): void
    {
        $path = $this->fieldsetPathFor($fieldsetHandle);
        if (! $this->files->exists($path)) {
            return;
        }

        $data = YAML::file($path)->parse();
        $data['title'] = $newTitle;
        $this->files->put($path, YAML::dump($data));
    }

    public function viewSlugFor(string $name): string
    {
        return Str::slug($name, '-', Config::getShortLocale());
    }

    public function fieldsetSlugFor(string $name): string
    {
        return Str::slug($name, '_', Config::getShortLocale());
    }

    private function viewSlugFromFieldsetHandle(string $fieldsetHandle): string
    {
        return Str::replace('_', '-', $fieldsetHandle);
    }

    public function fieldsetPathFor(string $handle): string
    {
        return config('statamic.bedrock.scaffold.fieldsets_path')."/{$handle}.yaml";
    }

    public function viewPathFor(string $view): string
    {
        return $this->target->viewsPath()."/{$view}.antlers.html";
    }

    private function moveReplacing(string $oldPath, string $newPath, string $label): ?string
    {
        if (! $this->files->exists($oldPath)) {
            return "Note: {$label} file not found at {$oldPath}";
        }

        if ($this->files->exists($newPath)) {
            $this->files->delete($newPath);
        }

        $this->files->move($oldPath, $newPath);

        return null;
    }
}
