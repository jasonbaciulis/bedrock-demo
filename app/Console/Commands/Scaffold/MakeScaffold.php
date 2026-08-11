<?php

namespace App\Console\Commands\Scaffold;

use App\Support\Scaffold\FieldsetFiles;
use App\Support\Scaffold\ScaffoldTarget;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Throwable;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;
use function Laravel\Prompts\suggest;
use function Laravel\Prompts\text;

final class MakeScaffold
{
    private readonly FieldsetFiles $fieldsetFiles;

    public function __construct(
        Filesystem $files,
        private readonly ScaffoldTarget $target,
        private readonly string $namePlaceholder,
    ) {
        $this->fieldsetFiles = new FieldsetFiles($files, $target);
    }

    public function run(?string $group, ?string $name, ?string $instructions, bool $force): int
    {
        $noun = $this->target->noun();

        $groups = $this->target->yaml->groups();
        $group = $group ?: select(label: "Which group should this {$noun} belong to?", options: $groups, required: true);

        $name = $name ?: $this->promptForName($group);

        $instructions = (string) ($instructions ?? text(
            label: 'What should be the instructions?',
            placeholder: '(Optional) Short guidance to editors'
        ));

        $view = $this->fieldsetFiles->viewSlugFor($name);
        $fieldset = $this->fieldsetFiles->fieldsetSlugFor($name);

        try {
            $this->fieldsetFiles->assertWritable($fieldset, $view, $force);
            $this->fieldsetFiles->createFromStub("fieldset_{$noun}.yaml.stub", $this->fieldsetFiles->fieldsetPathFor($fieldset), $name);
            $this->fieldsetFiles->createFromStub("{$noun}.antlers.html.stub", $this->fieldsetFiles->viewPathFor($view), $name);
            $this->target->yaml->addSet($group, $fieldset, [
                'display' => $name,
                'instructions' => $instructions,
                'fields' => [['import' => $fieldset]],
            ]);
        } catch (Throwable $throwable) {
            error($throwable->getMessage());

            return Command::FAILURE;
        }

        info("Created '{$name}' {$noun} in '{$groups[$group]}' group.");

        return Command::SUCCESS;
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
}
