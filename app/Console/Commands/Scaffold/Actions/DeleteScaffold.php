<?php

declare(strict_types=1);

namespace App\Console\Commands\Scaffold\Actions;

use App\Console\Commands\Scaffold\Contracts\ScaffoldTarget;
use App\Console\Commands\Scaffold\Support\EntryContentUpdater;
use App\Console\Commands\Scaffold\Support\FieldsetFiles;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;
use Statamic\Contracts\Entries\Entry;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;
use function Laravel\Prompts\warning;

final readonly class DeleteScaffold
{
    private FieldsetFiles $fieldsetFiles;

    private EntryContentUpdater $entryContent;

    public function __construct(Filesystem $files, private ScaffoldTarget $target)
    {
        $this->fieldsetFiles = new FieldsetFiles($files, $target);
        $this->entryContent = new EntryContentUpdater($target);
    }

    public function handle(?string $group, ?string $fieldset, bool $keepFiles, bool $force): int
    {
        $groups = $this->target->yaml->groups();
        if ($groups === []) {
            error("No groups found in {$this->target->yaml->fileName()}.");

            return Command::FAILURE;
        }

        $group = $group ?: $this->promptForGroup($groups);

        $sets = $this->target->yaml->sets($group);
        if ($sets === []) {
            info("The '{$groups[$group]}' group has no ".Str::plural($this->target->noun()).'.');

            return Command::SUCCESS;
        }

        $fieldset = $fieldset ?: $this->promptForSet($sets);
        $label = $sets[$fieldset] ?? $fieldset;

        $entriesUsing = $this->entryContent->entriesUsing($fieldset);
        $this->warnWhenEntriesUseSet($entriesUsing, $label);

        if (! $this->confirmsDeletion($label, $groups[$group], $keepFiles, $force)) {
            info('Aborted.');

            return Command::SUCCESS;
        }

        try {
            $this->target->yaml->removeSet($group, $fieldset);

            if (! $keepFiles) {
                $this->fieldsetFiles->deleteFor($fieldset, $force);
            }

            $this->removeUsagesFromEntries($entriesUsing, $fieldset);
        } catch (RuntimeException $runtimeException) {
            error($runtimeException->getMessage());

            return Command::FAILURE;
        }

        info("Removed '{$label}' {$this->target->noun()}.");

        return Command::SUCCESS;
    }

    /**
     * @param  array<string, string>  $groups
     */
    private function promptForGroup(array $groups): string
    {
        return select(label: "Which group contains the {$this->target->noun()}?", options: $groups, required: true);
    }

    /**
     * @param  array<string, string>  $sets
     */
    private function promptForSet(array $sets): string
    {
        return select(label: "Which {$this->target->noun()} would you like to delete?", options: $sets, required: true);
    }

    /**
     * @param  Collection<int, Entry>  $entriesUsing
     */
    private function warnWhenEntriesUseSet(Collection $entriesUsing, string $label): void
    {
        if ($entriesUsing->isEmpty()) {
            return;
        }

        $usingLabel = Str::plural('entry', $entriesUsing->count());

        warning(
            "Heads up: '{$label}' {$this->target->noun()} is used in {$entriesUsing->count()} {$usingLabel}. It will be removed from the {$usingLabel}."
        );
    }

    private function confirmsDeletion(string $label, string $groupLabel, bool $keepFiles, bool $force): bool
    {
        return $force || confirm(
            label: "Delete '{$label}' from '{$groupLabel}' group?",
            default: false,
            hint: $keepFiles
                ? "Only remove from {$this->target->yaml->fileName()} (files will be kept)."
                : "This will also delete the fieldset and {$this->target->noun()} view file."
        );
    }

    /**
     * @param  Collection<int, Entry>  $entriesUsing
     */
    private function removeUsagesFromEntries(Collection $entriesUsing, string $fieldset): void
    {
        $removedCount = $this->entryContent->deleteUsagesIn($entriesUsing, $fieldset);

        if ($removedCount > 0) {
            info("Removed from {$removedCount} ".Str::plural('entry', $removedCount).'.');
        }
    }
}
