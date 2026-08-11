<?php

declare(strict_types=1);

namespace App\Console\Commands\Scaffold\Actions;

use App\Console\Commands\Scaffold\Contracts\ScaffoldTarget;
use App\Console\Commands\Scaffold\Support\FieldsetFiles;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;
use function Laravel\Prompts\suggest;
use function Laravel\Prompts\text;

final readonly class MakeScaffold
{
    private FieldsetFiles $fieldsetFiles;

    public function __construct(
        Filesystem $files,
        private ScaffoldTarget $target,
        private string $namePlaceholder,
    ) {
        $this->fieldsetFiles = new FieldsetFiles($files, $target);
    }

    public function handle(?string $group, ?string $name, ?string $instructions, bool $force): int
    {
        $groups = $this->target->yaml->groups();
        if ($groups === []) {
            error("No groups found in {$this->target->yaml->fileName()}.");

            return Command::FAILURE;
        }

        $group = $group ?: $this->promptForGroup($groups);
        $name = $name ?: $this->promptForName($group);
        $instructions ??= $this->promptForInstructions();

        try {
            $this->createFieldsetAndView($name, $force);
            $this->registerInGroup($group, $name, $instructions);
        } catch (RuntimeException $exception) {
            error($exception->getMessage());

            return Command::FAILURE;
        }

        info("Created '{$name}' {$this->target->noun()} in '{$groups[$group]}' group.");

        return Command::SUCCESS;
    }

    /**
     * @param  array<string, string>  $groups
     */
    private function promptForGroup(array $groups): string
    {
        return select(label: "Which group should this {$this->target->noun()} belong to?", options: $groups, required: true);
    }

    private function promptForName(string $group): string
    {
        return suggest(
            label: "What should the {$this->target->noun()} be named?",
            options: $this->target->nameSuggestions($group),
            placeholder: $this->namePlaceholder,
            required: true,
        );
    }

    private function promptForInstructions(): string
    {
        return text(
            label: 'What should be the instructions?',
            placeholder: '(Optional) Short guidance to editors'
        );
    }

    private function createFieldsetAndView(string $name, bool $force): void
    {
        $noun = $this->target->noun();
        $fieldset = $this->fieldsetFiles->fieldsetSlugFor($name);
        $view = $this->fieldsetFiles->viewSlugFor($name);

        $this->fieldsetFiles->assertWritable($fieldset, $view, $force);
        $this->fieldsetFiles->createFromStub("fieldset_{$noun}.yaml.stub", $this->fieldsetFiles->fieldsetPathFor($fieldset), $name);
        $this->fieldsetFiles->createFromStub("{$noun}.antlers.html.stub", $this->fieldsetFiles->viewPathFor($view), $name);
    }

    private function registerInGroup(string $group, string $name, string $instructions): void
    {
        $fieldset = $this->fieldsetFiles->fieldsetSlugFor($name);

        $this->target->yaml->addSet($group, $fieldset, [
            'display' => $name,
            'instructions' => $instructions,
            'fields' => [['import' => $fieldset]],
        ]);
    }
}
