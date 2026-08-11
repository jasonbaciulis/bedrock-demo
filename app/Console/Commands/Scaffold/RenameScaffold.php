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
use function Laravel\Prompts\text;

final class RenameScaffold
{
    private readonly FieldsetFiles $fieldsetFiles;

    private readonly EntryContentUpdater $entryContent;

    public function __construct(
        Filesystem $files,
        private readonly ScaffoldTarget $target,
        private readonly string $namePlaceholder,
    ) {
        $this->fieldsetFiles = new FieldsetFiles($files, $target);
        $this->entryContent = new EntryContentUpdater($target);
    }

    public function run(?string $group, ?string $currentHandle, ?string $newName, bool $force): int
    {
        $noun = $this->target->noun();

        $groups = $this->target->yaml->groups();
        if ($groups === []) {
            error("No groups found in {$this->target->yaml->fileName()}.");

            return Command::FAILURE;
        }

        $hasArguments = $group && $currentHandle;

        $currentGroup = $group ?: select(label: "Which group contains the {$noun}?", options: $groups, required: true);

        $sets = $this->target->yaml->sets($currentGroup);
        if ($sets === []) {
            info("The '{$groups[$currentGroup]}' group has no ".Str::plural($noun).'.');

            return Command::SUCCESS;
        }

        $currentHandle = $currentHandle ?: select(label: "Which {$noun} would you like to rename?", options: $sets, required: true);

        $newName = $newName ?: text(
            label: "What should the new {$noun} name be?",
            placeholder: $this->namePlaceholder,
            required: true
        );

        $newView = $this->fieldsetFiles->viewSlugFor($newName);
        $newFieldset = $this->fieldsetFiles->fieldsetSlugFor($newName);

        $targetGroup = $currentGroup;
        if (! $hasArguments && confirm("Move this {$noun} to a different group?", default: false)) {
            $targetGroup = select(label: 'Select the new group', options: $groups, required: true);
        }

        try {
            $this->fieldsetFiles->assertWritable($newFieldset, $newView, $force);

            $currentName = $sets[$currentHandle] ?? null;

            if (
                ! $force &&
                ! confirm(
                    "Rename {$noun} '{$currentName}' to '{$newName}'? This will update all content entries."
                )
            ) {
                info('Rename cancelled.');

                return Command::SUCCESS;
            }

            $originalView = $currentName ? $this->fieldsetFiles->viewSlugFor($currentName) : $currentHandle;

            foreach ($this->fieldsetFiles->moveFor($currentHandle, $originalView, $newFieldset, $newView) as $note) {
                info($note);
            }

            $this->fieldsetFiles->updateFieldsetTitle($newFieldset, $newName);
            $this->target->yaml->renameSet($currentGroup, $targetGroup, $currentHandle, $newFieldset, $newName);

            $updatedEntries = $this->entryContent->renameUsages($currentHandle, $newFieldset);
            if ($updatedEntries > 0) {
                info("Updated {$updatedEntries} content ".Str::plural('entry', $updatedEntries));
            }
        } catch (Throwable $throwable) {
            error($throwable->getMessage());

            return Command::FAILURE;
        }

        info("Successfully renamed {$noun} '{$currentName}' to '{$newName}'");

        return Command::SUCCESS;
    }
}
