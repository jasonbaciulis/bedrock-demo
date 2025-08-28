<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use App\Support\Statamic\BlocksYaml;
use App\Actions\Blocks\DeleteBlockAction;
use function Laravel\Prompts\{select, confirm, info, warning};

class DeleteBlock extends Command
{
    protected $signature = 'delete:block
        {group? : The group handle (e.g. hero)}
        {block? : The block (fieldset) handle to delete}
        {--keep-files : Only remove from blocks.yaml; keep fieldset/view files}
        {--force : Ignore missing files when deleting}';

    protected $description = 'Delete a Statamic page builder block.';

    public function __construct(
        private readonly Filesystem $files,
        private readonly BlocksYaml $blocks,
        private readonly DeleteBlockAction $deleteBlock
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        // 1) Pick group (associative options; returns the key/handle)
        $groups = $this->blocks->groups(); // ['hero' => 'Hero Blocks', ...]
        if (empty($groups)) {
            warning('No groups found in blocks.yaml.');
            return self::FAILURE;
        }

        $group =
            $this->argument('group') ?:
            select(label: 'Which group contains the block?', options: $groups, required: true);

        // 2) Pick block within the group (associative; returns fieldset handle)
        $sets = $this->blocks->sets($group); // ['hero_simple' => 'Hero Simple', ...]
        if (empty($sets)) {
            warning("The '{$groups[$group]}' group has no blocks.");
            return self::SUCCESS;
        }

        $fieldset =
            $this->argument('block') ?:
            select(label: 'Which block would you like to delete?', options: $sets, required: true);

        $label = $sets[$fieldset] ?? $fieldset;

        // 3) Confirm destructive action
        if (
            !confirm(
                label: "Delete '{$label}' from '{$groups[$group]}'?",
                hint: $this->option('keep-files')
                    ? 'Only remove from blocks.yaml (files will be kept).'
                    : 'This will also delete the fieldset YAML and block view file.',
                default: false
            )
        ) {
            info('Aborted.');
            return self::SUCCESS;
        }

        // 4) Delegate to action
        try {
            ($this->deleteBlock)(
                group: $group,
                fieldset: $fieldset,
                keepFiles: (bool) $this->option('keep-files'),
                force: (bool) $this->option('force')
            );
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("Removed '{$label}' block.");
        return self::SUCCESS;
    }
}
