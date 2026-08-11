<?php

declare(strict_types=1);

namespace App\Console\Commands\Scaffold\Targets;

use App\Console\Commands\Scaffold\Contracts\ScaffoldTarget;
use App\Console\Commands\Scaffold\Yaml\ArticleYaml;
use Closure;
use Illuminate\Support\Arr;

final readonly class ArticleSetTarget implements ScaffoldTarget
{
    public function __construct(public ArticleYaml $yaml) {}

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
