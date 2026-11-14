<?php

declare(strict_types=1);

namespace App\Actions\StarterKit;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Statamic\Facades\YAML;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

final readonly class FindMissingFieldsets
{
    /**
     * @return list<string>
     */
    public function handle(string $exportPath): array
    {
        $yamlFiles = Finder::create()
            ->files()
            ->in(["{$exportPath}/resources/fieldsets", "{$exportPath}/resources/blueprints"])
            ->name('*.yaml');
        $problems = [];

        foreach ($yamlFiles as $yaml) {
            array_push($problems, ...$this->missingFieldsetsOf($yaml, $exportPath));
        }

        return $problems;
    }

    /**
     * @return list<string>
     */
    private function missingFieldsetsOf(SplFileInfo $yaml, string $exportPath): array
    {
        $relativePath = Str::after($yaml->getPathname(), "{$exportPath}/");

        $missingFieldsets = array_filter(
            array_unique($this->fieldsetHandlesIn(YAML::parse($yaml->getContents()))),
            fn (string $handle): bool => ! $this->fieldsetExists($exportPath, $handle),
        );

        return array_values(array_map(
            fn (string $handle): string => "{$relativePath} uses the fieldset {$handle}, which was not exported",
            $missingFieldsets,
        ));
    }

    /**
     * A field entry refers to a fieldset with `import: buttons` or with `field: common.text_plain`,
     * which is the `text_plain` field of the `common` fieldset.
     *
     * @param  array<array-key, mixed>  $node
     * @return list<string>
     */
    private function fieldsetHandlesIn(array $node): array
    {
        $handles = [];

        if (is_string($node['import'] ?? null)) {
            $handles[] = $node['import'];
        }

        if (isset($node['handle']) && is_string($node['field'] ?? null)) {
            $handles[] = Str::beforeLast($node['field'], '.');
        }

        foreach ($node as $child) {
            if (is_array($child)) {
                array_push($handles, ...$this->fieldsetHandlesIn($child));
            }
        }

        return $handles;
    }

    private function fieldsetExists(string $exportPath, string $handle): bool
    {
        return File::isFile("{$exportPath}/resources/fieldsets/".Str::replace('.', '/', $handle).'.yaml');
    }
}
