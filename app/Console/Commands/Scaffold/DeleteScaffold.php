<?php

namespace App\Console\Commands\Scaffold;

use App\Support\Scaffold\EntryContentUpdater;
use App\Support\Scaffold\FieldsetFiles;
use App\Support\Scaffold\ScaffoldTarget;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Throwable;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;
use function Laravel\Prompts\warning;

final class DeleteScaffold
{
    private readonly FieldsetFiles $fieldsetFiles;

    private readonly EntryContentUpdater $entryContent;

    public function __construct(Filesystem $files, private readonly ScaffoldTarget $target)
    {
        $this->fieldsetFiles = new FieldsetFiles($files, $target);
        $this->entryContent = new EntryContentUpdater($target);
    }

    public function run(?string $group, ?string $handle, bool $keepFiles, bool $force): int
    {
        $noun = $this->target->noun();

        $groups = $this->target->yaml->groups();
        if ($groups === []) {
            error("No groups found in {$this->target->yaml->fileName()}.");

            return Command::FAILURE;
        }

        $group = $group ?: select(label: "Which group contains the {$noun}?", options: $groups, required: true);

        $sets = $this->target->yaml->sets($group);
        if ($sets === []) {
            info("The '{$groups[$group]}' group has no ".Str::plural($noun).'.');

            return Command::SUCCESS;
        }

        $fieldset = $handle ?: select(label: "Which {$noun} would you like to delete?", options: $sets, required: true);

        $label = $sets[$fieldset] ?? $fieldset;

        $entriesUsing = $this->entryContent->entriesUsing($fieldset);
        if ($entriesUsing->isNotEmpty()) {
            $usingLabel = Str::plural('entry', $entriesUsing->count());
            warning(
                "Heads up: '{$label}' {$noun} is used in {$entriesUsing->count()} {$usingLabel}. It will be removed from the {$usingLabel}."
            );
        }

        if (
            ! confirm(
                label: "Delete '{$label}' from '{$groups[$group]}' group?",
                default: false,
                hint: $keepFiles
                    ? "Only remove from {$this->target->yaml->fileName()} (files will be kept)."
                    : "This will also delete the fieldset and {$noun} view file."
            )
        ) {
            info('Aborted.');

            return Command::SUCCESS;
        }

        try {
            $this->target->yaml->removeSet($group, $fieldset);

            if (! $keepFiles) {
                $this->fieldsetFiles->deleteFor($fieldset, $force);
            }

            $removedCount = $this->entryContent->deleteUsagesIn($entriesUsing, $fieldset);
            if ($removedCount > 0) {
                info("Removed from {$removedCount} ".Str::plural('entry', $removedCount).'.');
            }
        } catch (Throwable $throwable) {
            error($throwable->getMessage());

            return Command::FAILURE;
        }

        info("Removed '{$label}' {$noun}.");

        return Command::SUCCESS;
    }
}
