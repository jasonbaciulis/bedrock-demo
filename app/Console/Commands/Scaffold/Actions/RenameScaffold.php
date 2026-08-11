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
use function Laravel\Prompts\text;

final readonly class RenameScaffold
{
    private FieldsetFiles $fieldsetFiles;

    private EntryContentUpdater $entryContent;

    public function __construct(
        Filesystem $files,
        private ScaffoldTarget $target,
        private string $namePlaceholder,
    ) {
        $this->fieldsetFiles = new FieldsetFiles($files, $target);
        $this->entryContent = new EntryContentUpdater($target);
    }

    public function handle(?string $group, ?string $currentHandle, ?string $newName, bool $force): int
    {
        $groups = $this->target->yaml->groups();
        if ($groups === []) {
            error("No groups found in {$this->target->yaml->fileName()}.");

            return Command::FAILURE;
        }

        $hasArguments = $group && $currentHandle;

        $currentGroup = $group ?: $this->promptForGroup($groups);

        $sets = $this->target->yaml->sets($currentGroup);
        if ($sets === []) {
            info("The '{$groups[$currentGroup]}' group has no ".Str::plural($this->target->noun()).'.');

            return Command::SUCCESS;
        }

        $currentHandle = $currentHandle ?: $this->promptForSet($sets);

        $currentName = $sets[$currentHandle] ?? null;
        if ($currentName === null) {
            error("The '{$currentHandle}' {$this->target->noun()} was not found in the '{$groups[$currentGroup]}' group.");

            return Command::FAILURE;
        }

        $newName = $newName ?: $this->promptForNewName();
        $targetGroup = $hasArguments ? $currentGroup : $this->promptForTargetGroup($currentGroup, $groups);

        $newFieldset = $this->fieldsetFiles->fieldsetSlugFor($newName);
        $newView = $this->fieldsetFiles->viewSlugFor($newName);

        try {
            $this->fieldsetFiles->assertWritable($newFieldset, $newView, $force);

            if (! $this->confirmsRename($currentName, $newName, $force)) {
                info('Rename cancelled.');

                return Command::SUCCESS;
            }

            $this->moveFieldsetAndView($currentHandle, $currentName, $newFieldset, $newView);
            $this->fieldsetFiles->updateFieldsetTitle($newFieldset, $newName);
            $this->target->yaml->renameSet($currentGroup, $targetGroup, $currentHandle, $newFieldset, $newName);
            $this->renameUsagesInEntries($currentHandle, $newFieldset);
        } catch (RuntimeException $runtimeException) {
            error($runtimeException->getMessage());

            return Command::FAILURE;
        }

        info("Renamed '{$currentName}' {$this->target->noun()} to '{$newName}'.");

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
        return select(label: "Which {$this->target->noun()} would you like to rename?", options: $sets, required: true);
    }

    private function promptForNewName(): string
    {
        return text(
            label: "What should the new {$this->target->noun()} name be?",
            placeholder: $this->namePlaceholder,
            required: true
        );
    }

    /**
     * @param  array<string, string>  $groups
     */
    private function promptForTargetGroup(string $currentGroup, array $groups): string
    {
        if (! confirm("Move this {$this->target->noun()} to a different group?", default: false)) {
            return $currentGroup;
        }

        return select(label: 'Select the new group', options: $groups, required: true);
    }

    private function confirmsRename(string $currentName, string $newName, bool $force): bool
    {
        return $force || confirm(
            "Rename {$this->target->noun()} '{$currentName}' to '{$newName}'? This will update all content entries."
        );
    }

    private function moveFieldsetAndView(string $currentHandle, string $currentName, string $newFieldset, string $newView): void
    {
        $originalView = $this->fieldsetFiles->viewSlugFor($currentName);

        foreach ($this->fieldsetFiles->moveFor($currentHandle, $originalView, $newFieldset, $newView) as $note) {
            info($note);
        }
    }

    private function renameUsagesInEntries(string $currentHandle, string $newFieldset): void
    {
        $updatedEntries = $this->entryContent->renameUsages($currentHandle, $newFieldset);

        if ($updatedEntries > 0) {
            info("Updated {$updatedEntries} content ".Str::plural('entry', $updatedEntries).'.');
        }
    }
}
