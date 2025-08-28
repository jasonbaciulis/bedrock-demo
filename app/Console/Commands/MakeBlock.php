<?php

namespace App\Console\Commands;

use Illuminate\Support\Str;
use Statamic\Facades\Config;
use Illuminate\Console\Command;
use App\Support\Statamic\BlocksYaml;
use Illuminate\Filesystem\Filesystem;
use App\Actions\Blocks\MakeBlockAction;
use function Laravel\Prompts\{select, suggest, text};

class MakeBlock extends Command
{
    protected $signature = 'make:block
        {group? : The group handle (e.g. hero)}
        {name? : The block display name}
        {--instructions= : Editor instructions}
        {--force : Overwrite existing files}';

    protected $description = 'Create a new Statamic page builder block';

    public function __construct(
        private readonly Filesystem $files,
        private readonly BlocksYaml $blocks,
        private readonly MakeBlockAction $makeBlock
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        // 1) Resolve input (prompt if missing)
        $groups = $this->blocks->groups(); // ['hero' => 'Hero Blocks', ...]
        $group =
            $this->argument('group') ?:
            select(label: 'Which type of block would you like?', options: $groups, required: true);

        $suggestions = $this->suggestedBlocksFor($group);
        $name =
            $this->argument('name') ?:
            suggest(
                label: 'What should the block be named?',
                options: $suggestions,
                placeholder: 'e.g. Hero Simple',
                required: true
            );

        $instructions =
            (string) ($this->option('instructions') ??
                text(
                    label: 'What should be the instructions?',
                    placeholder: '(Optional) Short guidance to editors'
                ));

        // 2) Compute slugs once
        $locale = Config::getShortLocale();
        $viewName = Str::slug($name, '-', $locale);
        $fieldsetName = Str::slug($name, '_', $locale);

        // 3) Delegate all IO / mutations to the Action
        try {
            ($this->makeBlock)(
                group: $group,
                displayName: $name,
                fieldset: $fieldsetName,
                view: $viewName,
                instructions: $instructions,
                force: (bool) $this->option('force')
            );
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info("Created '{$name}' block in '{$groups[$group]}' group.");
        return self::SUCCESS;
    }

    /** Keep suggestions local & simple. Extract later if it grows. */
    private function suggestedBlocksFor(string $group): array
    {
        $map = [
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

        return $map[$group] ?? [];
    }
}
