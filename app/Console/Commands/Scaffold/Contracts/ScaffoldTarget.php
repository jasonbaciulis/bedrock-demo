<?php

declare(strict_types=1);

namespace App\Console\Commands\Scaffold\Contracts;

use App\Console\Commands\Scaffold\Yaml\GroupedSetsYaml;
use Closure;

/**
 * Describes everything that differs between the two scaffoldable content types:
 * where their sets live in YAML, where their views live, and how usages appear
 * in entry content.
 */
interface ScaffoldTarget
{
    public GroupedSetsYaml $yaml { get; }

    /**
     * Singular noun used in prompts, messages, and stub names ('block' or 'set').
     */
    public function noun(): string;

    /**
     * Entry field handle the scaffolded sets live in ('blocks' or 'article').
     */
    public function entryField(): string;

    /**
     * Directory the Antlers views for this target live in.
     */
    public function viewsPath(): string;

    /**
     * Predicate deciding whether an entry content node uses the given fieldset.
     */
    public function usageMatcher(string $fieldset): Closure;

    /**
     * Mutation applied to a matching node to point it at the new handle.
     */
    public function usageRenamer(string $newHandle): Closure;

    /**
     * @return list<string>
     */
    public function nameSuggestions(string $group): array;
}
