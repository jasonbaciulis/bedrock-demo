<?php

namespace App\Console\Actions;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use App\Support\Yaml\ArticleYaml;

class MakeArticleSetAction
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly ArticleYaml $article
    ) {}

    public function __invoke(
        string $group,
        string $displayName,
        string $fieldset,
        string $view,
        bool $force = false
    ): void {
        $this->assertWritable($fieldset, $view, $force);

        $this->createFieldset($fieldset, $displayName);
        $this->createPartial($view, $displayName);

        $this->article->addSet($group, $fieldset, [
            'display' => $displayName,
            'fields' => [['import' => $fieldset]],
        ]);
    }

    private function assertWritable(string $fieldset, string $view, bool $force): void
    {
        $fieldsetPath = base_path("resources/fieldsets/{$fieldset}.yaml");
        $viewPath = base_path("resources/views/sets/{$view}.blade.php");

        foreach ([$fieldsetPath, $viewPath] as $p) {
            if ($this->files->exists($p) && !$force) {
                throw new \RuntimeException("File exists: {$p} (use --force to overwrite)");
            }
        }
    }

    private function createFieldset(string $fieldset, string $name): void
    {
        $stub = $this->files->get(
            app_path('Console/Commands/Scaffold/stubs/fieldset_set.yaml.stub')
        );
        $this->files->put(
            base_path("resources/fieldsets/{$fieldset}.yaml"),
            Str::of($stub)->replace('{{ name }}', $name)
        );
    }

    private function createPartial(string $view, string $name): void
    {
        $stub = $this->files->get(app_path('Console/Commands/Scaffold/stubs/set.blade.php.stub'));
        $this->files->put(
            base_path("resources/views/sets/{$view}.blade.php"),
            Str::of($stub)->replace('{{ name }}', $name)
        );
    }
}
