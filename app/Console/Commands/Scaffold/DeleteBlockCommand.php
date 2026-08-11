<?php

declare(strict_types=1);

namespace App\Console\Commands\Scaffold;

use App\Console\Commands\Scaffold\Actions\DeleteScaffoldFiles;
use App\Console\Commands\Scaffold\Actions\RemoveSetUsages;
use App\Console\Commands\Scaffold\Enums\ScaffoldType;
use App\Console\Commands\Scaffold\Support\ScaffoldPrompts;
use App\Console\Commands\Scaffold\Support\ScaffoldRegistry;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;
use Statamic\Entries\Entry as EntryInstance;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

#[Description('Delete a Statamic page builder block.')]
#[Signature('delete:bedrock-block
        {group? : The group handle (e.g. hero)}
        {block? : The block (fieldset) handle to delete}
        {--keep-files : Only remove from blocks.yaml; keep fieldset/view files}
        {--force : Skip confirmation and ignore missing files}')]
final class DeleteBlockCommand extends Command
{
    private const ScaffoldType TYPE = ScaffoldType::Block;

    private ScaffoldRegistry $scaffoldRegistry;

    private ScaffoldPrompts $prompts;

    public function handle(
        DeleteScaffoldFiles $deleteScaffoldFiles,
        RemoveSetUsages $removeSetUsages,
    ): int {
        $this->scaffoldRegistry = new ScaffoldRegistry(self::TYPE);
        $this->prompts = new ScaffoldPrompts(self::TYPE);

        $groups = $this->scaffoldRegistry->groups();
        if ($groups === []) {
            error("No groups found in {$this->scaffoldRegistry->fileName()}.");

            return self::FAILURE;
        }

        $group = $this->resolveGroup($groups);

        $sets = $this->scaffoldRegistry->setsIn($group);
        if ($sets === []) {
            info("The '{$groups[$group]}' group has no blocks.");

            return self::SUCCESS;
        }

        $fieldset = $this->resolveBlock($sets);
        $label = $sets[$fieldset] ?? $fieldset;

        $entriesUsing = self::TYPE->entriesUsing($fieldset);
        $this->warnWhenEntriesUseBlock($entriesUsing, $label);

        if (! $this->confirmsDeletion($label, $groups[$group])) {
            info('Aborted.');

            return self::SUCCESS;
        }

        try {
            $removedCount = $this->deleteBlock($deleteScaffoldFiles, $removeSetUsages, $group, $fieldset, $entriesUsing);
        } catch (RuntimeException $runtimeException) {
            error($runtimeException->getMessage());

            return self::FAILURE;
        }

        $this->reportRemoval($removedCount, $label);

        return self::SUCCESS;
    }

    /**
     * @param  array<string, string>  $groups
     */
    private function resolveGroup(array $groups): string
    {
        return $this->argument('group') ?: $this->prompts->existingGroup($groups);
    }

    /**
     * @param  array<string, string>  $sets
     */
    private function resolveBlock(array $sets): string
    {
        return $this->argument('block') ?: $this->prompts->setToDelete($sets);
    }

    /**
     * @param  Collection<int, EntryInstance>  $entriesUsing
     */
    private function warnWhenEntriesUseBlock(Collection $entriesUsing, string $label): void
    {
        if ($entriesUsing->isEmpty()) {
            return;
        }

        $usingLabel = Str::plural('entry', $entriesUsing->count());

        warning(
            "Heads up: '{$label}' block is used in {$entriesUsing->count()} {$usingLabel}. It will be removed from the {$usingLabel}."
        );
    }

    private function confirmsDeletion(string $label, string $groupLabel): bool
    {
        return (bool) $this->option('force') || $this->prompts->confirmsDeletion(
            $label,
            $groupLabel,
            (bool) $this->option('keep-files'),
            $this->scaffoldRegistry->fileName()
        );
    }

    /**
     * @param  Collection<int, EntryInstance>  $entriesUsing
     */
    private function deleteBlock(
        DeleteScaffoldFiles $deleteScaffoldFiles,
        RemoveSetUsages $removeSetUsages,
        string $group,
        string $fieldset,
        Collection $entriesUsing,
    ): int {
        $this->scaffoldRegistry->remove($group, $fieldset);

        if (! $this->option('keep-files')) {
            $deleteScaffoldFiles->handle(self::TYPE, $fieldset, (bool) $this->option('force'));
        }

        return $removeSetUsages->handle(self::TYPE, $entriesUsing, $fieldset);
    }

    private function reportRemoval(int $removedCount, string $label): void
    {
        if ($removedCount > 0) {
            info("Removed from {$removedCount} ".Str::plural('entry', $removedCount).'.');
        }

        info("Removed '{$label}' block.");
    }
}
