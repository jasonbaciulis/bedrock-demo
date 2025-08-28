<?php

namespace App\Console\Commands;

use Illuminate\Support\Str;
use Statamic\Facades\Config;
use Illuminate\Console\Command;
use App\Support\Statamic\ArticleYaml;
use Illuminate\Filesystem\Filesystem;
use App\Actions\Article\MakeArticleSetAction;
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
        private readonly ArticleYaml $article,
        private readonly MakeArticleSetAction $makeSet
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
                placeholder: 'e.g. Image Gallery'
            );

        $locale = Config::getShortLocale();
        $viewName = Str::slug($name, '-', $locale);
        $fieldsetName = Str::slug($name, '_', $locale);

        try {
            ($this->makeSet)(
                group: $group,
                displayName: $name,
                fieldset: $fieldsetName,
                view: $viewName,
                force: (bool) $this->option('force')
            );
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("Created '{$name}' set in '{$groups[$group]}' group.");
        return self::SUCCESS;
    }
}
