<?php

namespace App\Console\Commands\Scaffold;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use App\Support\Yaml\ArticleYaml;
use App\Console\Actions\DeleteArticleSetAction;
use function Laravel\Prompts\{select, confirm, info, warning};

class DeleteSet extends Command
{
    protected $signature = 'delete:set
        {group? : Group handle in Article}
        {set?   : Set (fieldset) handle to delete}
        {--keep-files : Only remove from article.yaml; keep fieldset/view files}
        {--force : Ignore missing files when deleting}';

    protected $description = 'Delete a Statamic Article set.';

    public function __construct(
        private readonly Filesystem $files,
        private readonly ArticleYaml $article,
        private readonly DeleteArticleSetAction $deleteSet
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $groups = $this->article->groups();
        if (empty($groups)) {
            warning('No groups found in article.yaml.');
            return self::FAILURE;
        }

        $group =
            $this->argument('group') ?:
            select(
                label: 'From which group would you like to delete?',
                options: $groups,
                required: true
            );

        $sets = $this->article->sets($group);
        if (empty($sets)) {
            info("The '{$groups[$group]}' group has no sets.");
            return self::SUCCESS;
        }

        $fieldset =
            $this->argument('set') ?:
            select(label: 'Which set would you like to delete?', options: $sets, required: true);

        $label = $sets[$fieldset] ?? $fieldset;

        if (
            !confirm(
                label: "Delete '{$label}' from '{$groups[$group]}'?",
                hint: $this->option('keep-files')
                    ? 'Only remove from article.yaml (files will be kept).'
                    : 'This will also delete the fieldset YAML and set view file.',
                default: false
            )
        ) {
            info('Aborted.');
            return self::SUCCESS;
        }

        try {
            ($this->deleteSet)(
                group: $group,
                fieldset: $fieldset,
                keepFiles: (bool) $this->option('keep-files'),
                force: (bool) $this->option('force')
            );
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("Removed '{$label}' set.");
        return self::SUCCESS;
    }
}
