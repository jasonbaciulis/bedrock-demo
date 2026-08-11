<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\Config;
use Statamic\Facades\YAML;

/**
 * Everything the scaffold command tests need to know about the two scaffoldable
 * content types. Kept separate from App\Enums\ScaffoldType so the tests never
 * derive their expectations from the code under test.
 */
enum ScaffoldFixture
{
    case Block;
    case ArticleSet;

    /**
     * Fieldset handle and view slug the scaffold commands derive from a display
     * name. Spelled out here rather than through App\Support\ScaffoldName, so a
     * change in that class fails the assertions instead of moving them.
     *
     * @return array{0: string, 1: string}
     */
    public static function handles(string $display): array
    {
        $locale = Config::getShortLocale();

        return [Str::slug($display, '_', $locale), Str::slug($display, '-', $locale)];
    }

    public function noun(): string
    {
        return match ($this) {
            self::Block => 'block',
            self::ArticleSet => 'set',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::Block => 'messaging',
            self::ArticleSet => 'text_layout',
        };
    }

    public function groupDisplay(): string
    {
        return match ($this) {
            self::Block => 'Messaging',
            self::ArticleSet => 'Text & Layout',
        };
    }

    /**
     * Entry field, and parent fieldset handle, the scaffolded sets live in.
     */
    public function entryField(): string
    {
        return match ($this) {
            self::Block => 'blocks',
            self::ArticleSet => 'article',
        };
    }

    public function fieldType(): string
    {
        return match ($this) {
            self::Block => 'replicator',
            self::ArticleSet => 'bard',
        };
    }

    public function collection(): string
    {
        return match ($this) {
            self::Block => 'pages',
            self::ArticleSet => 'posts',
        };
    }

    public function command(string $verb): string
    {
        return "{$verb}:bedrock-{$this->noun()}";
    }

    /**
     * Scaffold a set with a unique display name.
     *
     * @return array{0: string, 1: string, 2: string} Display name, fieldset handle and view slug
     */
    public function create(): array
    {
        $display = 'Scaffold Test '.Str::random(6);
        [$fieldset, $view] = self::handles($display);

        test()->artisan($this->command('make'), [
            'group' => $this->group(),
            'name' => $display,
            '--instructions' => 'Test instructions',
            '--force' => true,
        ])->assertSuccessful();

        return [$display, $fieldset, $view];
    }

    public function fieldsetPath(string $fieldset): string
    {
        return Scratch::fieldsetsPath()."/{$fieldset}.yaml";
    }

    public function parentFieldsetPath(): string
    {
        return $this->fieldsetPath($this->entryField());
    }

    public function viewPath(string $view): string
    {
        return config()->string("statamic.bedrock.scaffold.{$this->noun()}s_views_path")."/{$view}.antlers.html";
    }

    /**
     * Sets the parent fieldset declares for this fixture's group.
     *
     * @return array<string, array<string, mixed>>
     */
    public function declaredSets(): array
    {
        $parent = YAML::file($this->parentFieldsetPath())->parse();

        return Arr::get(
            collect($parent['fields'])->firstWhere('handle', $this->entryField()),
            "field.sets.{$this->group()}.sets",
            [],
        );
    }

    /**
     * Entry content that uses the given fieldset once.
     *
     * @return list<array<string, mixed>>
     */
    public function entryContent(string $fieldset): array
    {
        return match ($this) {
            self::Block => [['type' => $fieldset, 'enabled' => true]],
            self::ArticleSet => [[
                'type' => 'set',
                'attrs' => ['id' => 'abc', 'values' => ['type' => $fieldset]],
            ]],
        };
    }

    /**
     * Fieldset handles the entry content currently uses.
     *
     * @return list<mixed>
     */
    public function usedHandles(Entry $entry): array
    {
        $path = match ($this) {
            self::Block => '*.type',
            self::ArticleSet => '*.attrs.values.type',
        };

        return data_get((array) $entry->get($this->entryField()), $path, []);
    }
}
