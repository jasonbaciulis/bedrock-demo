<?php

declare(strict_types=1);

namespace App\Console\Commands\Scaffold;

use App\Console\Commands\Scaffold\Actions\CreateScaffoldFiles;
use App\Console\Commands\Scaffold\Enums\ScaffoldType;
use App\Console\Commands\Scaffold\Support\ScaffoldName;
use App\Console\Commands\Scaffold\Support\ScaffoldPrompts;
use App\Console\Commands\Scaffold\Support\ScaffoldRegistry;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

#[Description('Create a new Statamic page builder block')]
#[Signature('make:bedrock-block
        {group? : The group handle (e.g. hero)}
        {name? : The block display name}
        {--instructions= : Editor instructions}
        {--force : Overwrite existing files}')]
final class MakeBlockCommand extends Command
{
    private const ScaffoldType TYPE = ScaffoldType::Block;

    private ScaffoldRegistry $scaffoldRegistry;

    private ScaffoldPrompts $prompts;

    public function handle(CreateScaffoldFiles $createScaffoldFiles): int
    {
        $this->scaffoldRegistry = new ScaffoldRegistry(self::TYPE);
        $this->prompts = new ScaffoldPrompts(self::TYPE);

        $groups = $this->scaffoldRegistry->groups();
        if ($groups === []) {
            error("No groups found in {$this->scaffoldRegistry->fileName()}.");

            return self::FAILURE;
        }

        $group = $this->resolveGroup($groups);
        $name = ScaffoldName::fromDisplay($this->resolveName($group));
        $instructions = $this->resolveInstructions();

        try {
            $createScaffoldFiles->handle(self::TYPE, $name, (bool) $this->option('force'));
            $this->registerInGroup($group, $name, $instructions);
        } catch (RuntimeException $runtimeException) {
            error($runtimeException->getMessage());

            return self::FAILURE;
        }

        info("Created '{$name->display}' block in '{$groups[$group]}' group.");

        return self::SUCCESS;
    }

    /**
     * @param  array<string, string>  $groups
     */
    private function resolveGroup(array $groups): string
    {
        return $this->argument('group') ?: $this->prompts->newGroup($groups);
    }

    private function resolveName(string $group): string
    {
        return $this->argument('name') ?: $this->prompts->name($group, placeholder: 'e.g. Hero Simple');
    }

    private function resolveInstructions(): string
    {
        return $this->option('instructions') ?? $this->prompts->instructions();
    }

    private function registerInGroup(string $group, ScaffoldName $name, string $instructions): void
    {
        $this->scaffoldRegistry->add($group, $name->fieldset, [
            'display' => $name->display,
            'instructions' => $instructions,
            'fields' => [['import' => $name->fieldset]],
        ]);
    }
}
