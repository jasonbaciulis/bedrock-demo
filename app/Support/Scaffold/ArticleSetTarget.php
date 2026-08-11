<?php

namespace App\Support\Scaffold;

use App\Support\Yaml\ArticleYaml;
use App\Support\Yaml\GroupedSetsYaml;
use Closure;
use Illuminate\Support\Arr;

final class ArticleSetTarget implements ScaffoldTarget
{
    public readonly GroupedSetsYaml $yaml;

    public function __construct(ArticleYaml $yaml)
    {
        $this->yaml = $yaml;
    }

    public function noun(): string
    {
        return 'set';
    }

    public function entryField(): string
    {
        return 'article';
    }

    public function viewsPath(): string
    {
        return config()->string('statamic.bedrock.scaffold.sets_views_path');
    }

    public function usageMatcher(string $fieldset): Closure
    {
        return static fn ($node): bool => is_array($node)
            && Arr::get($node, 'type') === 'set'
            && Arr::get($node, 'attrs.values.type') === $fieldset;
    }

    public function usageRenamer(string $newHandle): Closure
    {
        return static function (array $node) use ($newHandle): array {
            $node['attrs']['values']['type'] = $newHandle;

            return $node;
        };
    }

    public function nameSuggestions(string $group): array
    {
        return [];
    }
}
