<?php

namespace App\Console\Commands\Scaffold;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Statamic\Facades\Entry;
use Illuminate\Console\Command;
use App\Support\Yaml\BlocksYaml;
use Illuminate\Filesystem\Filesystem;
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
        private readonly BlocksYaml $blocks
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        // 1) Pick group (associative options; returns the key/handle)
        $groups = $this->blocks->groups();
        if (empty($groups)) {
            warning('No groups found in blocks.yaml.');

            return self::FAILURE;
        }

        $group =
            $this->argument('group') ?:
            select(label: 'Which group contains the block?', options: $groups, required: true);

        // 2) Pick block within the group (associative; returns fieldset handle)
        $sets = $this->blocks->sets($group);
        if (empty($sets)) {
            warning("The '{$groups[$group]}' group has no blocks.");

            return self::SUCCESS;
        }

        $fieldset =
            $this->argument('block') ?:
            select(label: 'Which block would you like to delete?', options: $sets, required: true);

        $blockLabel = $sets[$fieldset] ?? $fieldset;

        // 3) Inform usage counts and confirm destructive action
        $entriesCount = $this->countEntriesUsingBlock($fieldset);
        if ($entriesCount > 0) {
            $entriesLabel = Str::plural('entry', $entriesCount);
            warning(
                "Heads up: '{$blockLabel}' is used in {$entriesCount} {$entriesLabel}. It will be removed from the {$entriesLabel}."
            );
        }

        if (
            !confirm(
                label: "Delete '{$blockLabel}' from '{$groups[$group]}' group?",
                hint: $this->option('keep-files')
                    ? 'Only remove from blocks.yaml (files will be kept).'
                    : 'This will also delete the fieldset and block view file.',
                default: false
            )
        ) {
            info('Aborted.');

            return self::SUCCESS;
        }

        // 4) Perform deletion
        try {
            $this->blocks->removeSet($group, $fieldset);

            if (!(bool) $this->option('keep-files')) {
                $this->deleteFiles(fieldset: $fieldset, force: (bool) $this->option('force'));
            }

            // Also remove usages from entries (pages, etc.)
            $removedCount = $this->removeBlockUsagesFromEntries($fieldset);
            if ($removedCount > 0) {
                info("Removed from {$removedCount} {$entriesLabel}.");
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        info("Removed '{$blockLabel}' block.");

        return self::SUCCESS;
    }

    private function deleteFiles(string $fieldset, bool $force = false): void
    {
        $fieldsetPath = base_path("resources/fieldsets/{$fieldset}.yaml");
        $view = str_replace('_', '-', $fieldset);
        $viewPath = base_path("resources/views/blocks/{$view}.blade.php");

        $missing = [];

        if ($this->files->exists($fieldsetPath)) {
            $this->files->delete($fieldsetPath);
        } else {
            $missing[] = $fieldsetPath;
        }

        if ($this->files->exists($viewPath)) {
            $this->files->delete($viewPath);
        } else {
            $missing[] = $viewPath;
        }

        if ($missing && !$force) {
            $list = implode("\n - ", $missing);
            throw new \RuntimeException(
                "Some files were not found to delete:\n - {$list}\n(Use --force to ignore.)"
            );
        }
    }

    /**
     * Remove items of a given block type from all entries using the `blocks` field.
     */
    private function removeBlockUsagesFromEntries(string $fieldset): int
    {
        $totalRemoved = 0;

        foreach (Entry::all() as $entry) {
            $blocks = collect((array) $entry->get('blocks'));
            if ($blocks->isEmpty()) {
                continue;
            }

            $filtered = $blocks
                ->reject(
                    static fn($item): bool => is_array($item) &&
                        Arr::get($item, 'type') === $fieldset
                )
                ->values();

            $removed = $blocks->count() - $filtered->count();
            if ($removed > 0) {
                $entry->set('blocks', $filtered->all());
                $entry->save();
                $totalRemoved += $removed;
            }
        }

        return $totalRemoved;
    }

    private function countEntriesUsingBlock(string $fieldset): int
    {
        return Entry::all()
            ->filter(static function ($entry) use ($fieldset) {
                $blocks = collect((array) $entry->get('blocks'));
                if ($blocks->isEmpty()) {
                    return false;
                }

                return $blocks->contains(
                    static fn($item): bool => is_array($item) &&
                        Arr::get($item, 'type') === $fieldset
                );
            })
            ->count();
    }
}
