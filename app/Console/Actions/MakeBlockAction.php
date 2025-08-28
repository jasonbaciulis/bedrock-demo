<?php

namespace App\Console\Actions;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use App\Support\Yaml\BlocksYaml;

class MakeBlockAction
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly BlocksYaml $blocks
    ) {}

    public function __invoke(
        string $group,
        string $displayName,
        string $fieldset,
        string $view,
        string $instructions = '',
        bool $force = false
    ): void {
        $this->assertWritable($fieldset, $view, $force);

        $this->createFieldset($fieldset, $displayName);
        $this->createPartial($view, $displayName);

        $this->blocks->addSet($group, $fieldset, [
            'display' => $displayName,
            'instructions' => $instructions,
            'fields' => [['import' => $fieldset]],
        ]);
    }

    private function assertWritable(string $fieldset, string $view, bool $force): void
    {
        $fieldsetPath = base_path("resources/fieldsets/{$fieldset}.yaml");
        $viewPath = base_path("resources/views/blocks/{$view}.blade.php");

        foreach ([$fieldsetPath, $viewPath] as $path) {
            if ($this->files->exists($path) && !$force) {
                throw new \RuntimeException("File exists: {$path} (use --force to overwrite)");
            }
        }
    }

    private function createFieldset(string $fieldset, string $name): void
    {
        $stub = $this->files->get(
            app_path('Console/Commands/Scaffold/stubs/fieldset_block.yaml.stub')
        );
        $contents = Str::of($stub)->replace('{{ name }}', $name);
        $this->files->put(base_path("resources/fieldsets/{$fieldset}.yaml"), $contents);
    }

    private function createPartial(string $view, string $name): void
    {
        $stub = $this->files->get(app_path('Console/Commands/Scaffold/stubs/block.blade.php.stub'));
        $contents = Str::of($stub)->replace('{{ name }}', $name)->replace('{{ filename }}', $view);

        $this->files->put(base_path("resources/views/blocks/{$view}.blade.php"), $contents);
    }
}
