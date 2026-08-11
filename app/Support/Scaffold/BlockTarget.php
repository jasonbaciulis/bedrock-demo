<?php

namespace App\Support\Scaffold;

use App\Support\Yaml\BlocksYaml;
use App\Support\Yaml\GroupedSetsYaml;
use Closure;
use Illuminate\Support\Arr;

final class BlockTarget implements ScaffoldTarget
{
    /**
     * Name suggestions per group handle in blocks.yaml.
     *
     * @var array<string, list<string>>
     */
    private const array SUGGESTED_BLOCKS = [
        'hero' => [
            'Hero Simple',
            'Hero Split Image',
            'Hero Split Offset Image',
            'Hero Split Image Shapes',
            'Hero Background Image',
            'Hero App Screenshot',
        ],
        'messaging' => [
            'Features Split Image',
            'Features 2x2 Grid',
            'Features 3x2 Grid',
            'Features 4x2 Grid',
            'Features Offset 2x2 Grid',
            'Features Offset List',
            'Features Bento Grid',
            'Features Panel',
            'Features 3-column',
            'Benefits Split Image',
            'Benefits 2x2 Grid',
            'Benefits 3x2 Grid',
            'Benefits 4x2 Grid',
            'Benefits Offset 2x2 Grid',
            'Benefits Offset List',
            'Benefits Bento Grid',
            'Benefits Panel',
            'Benefits 3-column',
            'Steps',
            'Problem',
            'Solution',
            'Why Switch',
            'Before & After',
        ],
        'authority' => ['Testimonials', 'Logo Cloud', 'Stats', 'Awards', 'Ratings', 'Results'],
        'content' => [
            'Article',
            'FAQs',
            'Portfolio',
            'Blog Excerpt',
            'Blog Paginated',
            'Search Form',
            'Search Results',
            'Team',
            'Case Studies',
            'Content with Testimonial',
            'Split with Image',
        ],
        'conversion' => [
            'CTA Panel',
            'CTA Split Image',
            'CTA Simple',
            'CTA Simple Centered',
            'CTA Simple Justified',
            'CTA Simple on Brand',
            'Pricing',
            'Contact Form',
            'Newsletter',
        ],
        'special' => ['Google Map', 'Style Guide'],
    ];

    public readonly GroupedSetsYaml $yaml;

    public function __construct(BlocksYaml $yaml)
    {
        $this->yaml = $yaml;
    }

    public function noun(): string
    {
        return 'block';
    }

    public function entryField(): string
    {
        return 'blocks';
    }

    public function viewsPath(): string
    {
        return config()->string('statamic.bedrock.scaffold.blocks_views_path');
    }

    public function usageMatcher(string $fieldset): Closure
    {
        return static fn ($item): bool => is_array($item) && Arr::get($item, 'type') === $fieldset;
    }

    public function usageRenamer(string $newHandle): Closure
    {
        return static function (array $item) use ($newHandle): array {
            $item['type'] = $newHandle;

            return $item;
        };
    }

    public function nameSuggestions(string $group): array
    {
        return self::SUGGESTED_BLOCKS[$group] ?? [];
    }
}
