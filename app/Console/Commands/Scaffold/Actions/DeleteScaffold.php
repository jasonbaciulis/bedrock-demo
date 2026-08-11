<?php

declare(strict_types=1);

namespace App\Console\Commands\Scaffold\Actions;

use App\Console\Commands\Scaffold\Contracts\ScaffoldTarget;
use App\Console\Commands\Scaffold\Support\EntryContentUpdater;
use App\Console\Commands\Scaffold\Support\FieldsetFiles;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use RuntimeException;

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

        $fieldset = $fieldset ?: select(label: "Which {$noun} would you like to delete?", options: $sets, required: true);

        $label = $sets[$fieldset] ?? $fieldset;

        $entriesUsing = $this->entryContent->entriesUsing($fieldset);
        if ($entriesUsing->isNotEmpty()) {
            $usingLabel = Str::plural('entry', $entriesUsing->count());
            warning(
                "Heads up: '{$label}' {$noun} is used in {$entriesUsing->count()} {$usingLabel}. It will be removed from the {$usingLabel}."
            );
        }

        if (
            ! $force
            && ! confirm(
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
        } catch (RuntimeException $exception) {
            error($exception->getMessage());

            return Command::FAILURE;
        }

        info("Removed '{$label}' {$noun}.");

        return Command::SUCCESS;
    }
}
