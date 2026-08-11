<?php

declare(strict_types=1);

namespace App\Console\Commands\Scaffold;

use App\Console\Commands\Scaffold\Actions\MoveScaffoldFiles;
use App\Console\Commands\Scaffold\Actions\RenameSetUsages;
use App\Console\Commands\Scaffold\Enums\ScaffoldType;
use App\Console\Commands\Scaffold\Support\ScaffoldName;
use App\Console\Commands\Scaffold\Support\ScaffoldPrompts;
use App\Console\Commands\Scaffold\Support\ScaffoldRegistry;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RuntimeException;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

#[Description('Rename a Statamic Article set')]
#[Signature('rename:bedrock-set
        {group? : The group handle in Article}
        {current_name? : The current set handle to rename}
        {new_name? : The new set display name}
        {--force : Skip confirmation and overwrite existing files}')]
final class RenameSetCommand extends Command
{
    private const ScaffoldType TYPE = ScaffoldType::ArticleSet;

    private ScaffoldRegistry $scaffoldRegistry;

    private ScaffoldPrompts $prompts;

    public function handle(
        MoveScaffoldFiles $moveScaffoldFiles,
        RenameSetUsages $renameSetUsages,
    ): int {
        $this->scaffoldRegistry = new ScaffoldRegistry(self::TYPE);
        $this->prompts = new ScaffoldPrompts(self::TYPE);

        $groups = $this->scaffoldRegistry->groups();
        if ($groups === []) {
            error("No groups found in {$this->scaffoldRegistry->fileName()}.");

            return self::FAILURE;
        }

        $currentGroup = $this->resolveGroup($groups);

        $sets = $this->scaffoldRegistry->setsIn($currentGroup);
        if ($sets === []) {
            info("The '{$groups[$currentGroup]}' group has no sets.");

            return self::SUCCESS;
        }

        $currentHandle = $this->resolveSet($sets);

        $currentName = $sets[$currentHandle] ?? null;
        if ($currentName === null) {
            error("The '{$currentHandle}' set was not found in the '{$groups[$currentGroup]}' group.");

            return self::FAILURE;
        }

        $new = ScaffoldName::fromDisplay($this->resolveNewName());
        $targetGroup = $this->resolveTargetGroup($currentGroup, $groups);

        try {
            if (! $this->confirmsRename($currentName, $new->display)) {
                info('Rename cancelled.');

                return self::SUCCESS;
            }

            $updatedEntries = $this->renameSet($moveScaffoldFiles, $renameSetUsages, $currentGroup, $targetGroup, $currentHandle, $currentName, $new);
        } catch (RuntimeException $runtimeException) {
            error($runtimeException->getMessage());

            return self::FAILURE;
        }

        $this->reportRename($updatedEntries, $currentName, $new->display);

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
    private function resolveSet(array $sets): string
    {
        return $this->argument('current_name') ?: $this->prompts->setToRename($sets);
    }

    private function resolveNewName(): string
    {
        return $this->argument('new_name') ?: $this->prompts->newName(placeholder: 'e.g. Gallery Large');
    }

    /**
     * @param  array<string, string>  $groups
     */
    private function resolveTargetGroup(string $currentGroup, array $groups): string
    {
        if ($this->argument('group') && $this->argument('current_name')) {
            return $currentGroup;
        }

        return $this->prompts->targetGroup($currentGroup, $groups);
    }

    private function confirmsRename(string $currentName, string $newName): bool
    {
        return (bool) $this->option('force') || $this->prompts->confirmsRename($currentName, $newName);
    }

    private function renameSet(
        MoveScaffoldFiles $moveScaffoldFiles,
        RenameSetUsages $renameSetUsages,
        string $currentGroup,
        string $targetGroup,
        string $currentHandle,
        string $currentName,
        ScaffoldName $new,
    ): int {
        $currentView = ScaffoldName::fromDisplay($currentName)->view;

        foreach ($moveScaffoldFiles->handle(self::TYPE, $currentHandle, $currentView, $new, (bool) $this->option('force')) as $note) {
            info($note);
        }

        $this->scaffoldRegistry->rename($currentGroup, $targetGroup, $currentHandle, $new->fieldset, $new->display);

        return $renameSetUsages->handle(self::TYPE, $currentHandle, $new->fieldset);
    }

    private function reportRename(int $updatedEntries, string $currentName, string $newName): void
    {
        if ($updatedEntries > 0) {
            info("Updated {$updatedEntries} content ".Str::plural('entry', $updatedEntries).'.');
        }

        info("Renamed '{$currentName}' set to '{$newName}'.");
    }
}
