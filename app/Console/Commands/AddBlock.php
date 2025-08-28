<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Statamic\Console\RunsInPlease;
use Statamic\Facades\Config;
use Statamic\Support\Arr;
use Stringy\StaticStringy as Stringy;
use Symfony\Component\Yaml\Yaml;
use function Laravel\Prompts\{select, suggest, text};

class AddBlock extends Command
{
    use RunsInPlease;

    /**
     * The name of the console command.
     *
     * @var string
     */
    protected $name = 'make:block';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Statamic page builder block.';

    /**
     * The block name.
     *
     * @var string
     */
    protected $block_name = '';

    /**
     * The block fieldset filename.
     *
     * @var string
     */
    protected $fieldset_name = '';

    /**
     * The block view filename.
     *
     * @var string
     */
    protected $view_name = '';

    /**
     * The block instructions.
     *
     * @var string
     */
    protected $instructions = '';

    /**
     * The selected group.
     *
     * @var string
     */
    protected $selected_group = '';

    /**
     * Execute the console command.
     *
     * @return bool|null
     */
    public function handle()
    {
        // First ask for the group (nice arrow-key UI)
        $this->selected_group = $this->selectGroup();

        // Then ask for block name with rich autocomplete suggestions
        $suggestedBlocks = $this->getSuggestedBlocksForGroup($this->selected_group);
        $this->block_name = suggest(
            label: 'What should the block be named?',
            options: $suggestedBlocks,
            placeholder: 'e.g. Hero Split',
            required: true
        );

        $this->view_name = Stringy::slugify($this->block_name, '-', Config::getShortLocale());
        $this->fieldset_name = Stringy::slugify($this->block_name, '_', Config::getShortLocale());

        $this->instructions = text(
            label: 'What should be the instructions?',
            placeholder: '(Optional) Short guidance to editors',
            required: false
        );

        try {
            $this->checkExistence('Fieldset', "resources/fieldsets/{$this->fieldset_name}.yaml");
            $this->checkExistence('Partial', "resources/views/blocks/{$this->view_name}.blade.php");

            $this->createFieldset();
            $this->createPartial();
            $this->updateBlocks();
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }

        $this->info("Created '{$this->block_name}' block.");
    }

    /**
     * Select which group to install the block in.
     */
    protected function selectGroup(): string
    {
        $fieldset = Yaml::parseFile(base_path('resources/fieldsets/blocks.yaml'));
        $groups = Arr::get($fieldset, 'fields.0.field.sets', []);

        $options = collect($groups)
            ->mapWithKeys(function ($group, $handle) {
                $label = $group['display'] ?? Stringy::humanize($handle);
                return [$handle => (string) $label];
            })
            ->all();

        return select(
            label: 'Which type of block would you like?',
            options: $options,
            required: true
        );
    }

    /**
     * Get suggested block names for a group.
     */
    protected function getSuggestedBlocksForGroup(string $group): array
    {
        $suggestedBlocks = [
            'hero' => ['Hero Home', 'Hero Simple', 'Hero With Video', 'Hero With CTA'],
            'messaging' => [
                'Features',
                'Benefits',
                'How It Works',
                'Process Steps',
                'Value Proposition',
            ],
            'authority' => [
                'Testimonials',
                'Client Logos',
                'Trust Indicators',
                'Awards & Recognition',
                'Press Mentions',
            ],
            'content' => ['Case Studies', 'Portfolio'],
            'conversion' => ['Call to Action', 'Pricing Table', 'Demo Request'],
            'special' => ['Google Map'],
        ];

        return $suggestedBlocks[$group] ?? [];
    }

    /**
     * Check if a file already exists.
     *
     * @return bool|null
     */
    protected function checkExistence($type, $path)
    {
        if (File::exists(base_path($path))) {
            throw new \Exception("{$type} '{$path}' already exists.");
        }
    }

    /**
     * Create fieldset.
     *
     * @return bool|null
     */
    protected function createFieldset()
    {
        $stub = File::get(__DIR__ . '/stubs/fieldset_block.yaml.stub');
        $contents = Str::of($stub)->replace('{{ name }}', $this->block_name);

        File::put(base_path("resources/fieldsets/{$this->fieldset_name}.yaml"), $contents);
    }

    /**
     * Create partial.
     *
     * @return bool|null
     */
    protected function createPartial()
    {
        $stub = File::get(__DIR__ . '/stubs/block.blade.php.stub');
        $contents = Str::of($stub)
            ->replace('{{ name }}', $this->block_name)
            ->replace('{{ filename }}', $this->view_name);

        File::put(base_path("resources/views/blocks/{$this->view_name}.blade.php"), $contents);
    }

    /**
     * Update blocks.yaml.
     *
     * @return bool|null
     */
    protected function updateBlocks()
    {
        $fieldset = Yaml::parseFile(base_path('resources/fieldsets/blocks.yaml'));
        $newSet = [
            'display' => $this->block_name,
            'instructions' => $this->instructions,
            'fields' => [
                [
                    'import' => $this->fieldset_name,
                ],
            ],
        ];

        $existingGroups = Arr::get($fieldset, 'fields.0.field.sets');
        $groupSets = $existingGroups[$this->selected_group];
        $existingSets = Arr::get($groupSets, 'sets');
        $existingSets[$this->fieldset_name] = $newSet;
        $existingSets = collect($existingSets)
            ->sortBy(function ($value, $key) {
                return $key;
            })
            ->all();

        Arr::set($groupSets, 'sets', $existingSets);
        $existingGroups[$this->selected_group] = $groupSets;
        Arr::set($fieldset, 'fields.0.field.sets', $existingGroups);

        File::put(base_path('resources/fieldsets/blocks.yaml'), Yaml::dump($fieldset, 99, 2));
    }
}
