<?php

namespace App\Console\Commands\Scaffold;

use Illuminate\Support\Str;
use Statamic\Facades\Config;
use Illuminate\Console\Command;
use App\Support\Yaml\ArticleYaml;
use Illuminate\Filesystem\Filesystem;
use function Laravel\Prompts\{select, text};

class MakeSet extends Command
{
    protected $signature = 'make:set
        {group? : Group handle in Article}
        {name?  : Set display name}
        {--force : Overwrite existing files}';

    protected $description = 'Create a new Statamic Article set.';

    public function __construct(
        private readonly Filesystem $files,
        private readonly ArticleYaml $article
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $groups = $this->article->groups();
        $group =
            $this->argument('group') ?:
            select(
                label: 'Which group should this set belong to?',
                options: $groups,
                required: true
            );

        $name =
            $this->argument('name') ?:
            text(
                label: 'What should be the name for this set?',
                required: true,
                placeholder: 'e.g. Gallery'
            );

        $locale = Config::getShortLocale();
        $view = Str::slug($name, '-', $locale);
        $fieldset = Str::slug($name, '_', $locale);

        try {
            $this->assertWritable($fieldset, $view, (bool) $this->option('force'));
            $this->createFieldset($fieldset, $name);
            $this->createPartial($view, $name);
            $this->updateArticleFieldset($group, $fieldset, $name);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("Created '{$name}' set in '{$groups[$group]}' group.");
        return self::SUCCESS;
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

    private function updateArticleFieldset(string $group, string $fieldset, string $name): void
    {
        $this->article->addSet($group, $fieldset, [
            'display' => $name,
            'fields' => [['import' => $fieldset]],
        ]);
    }
}
