<?php

namespace App\Support\Statamic;

use Stringy\StaticStringy as Stringy;
use Statamic\Support\Arr;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Filesystem\Filesystem;

class BlocksYaml
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly string $path = 'resources/fieldsets/blocks.yaml'
    ) {}

    public function groups(): array
    {
        $data = $this->read();
        $raw = Arr::get($data, 'fields.0.field.sets', []);

        // ['hero' => 'Hero Blocks', ...]
        $pairs = [];
        foreach ($raw as $handle => $group) {
            $pairs[$handle] = $group['display'] ?? Stringy::humanize($handle);
        }

        return $pairs;
    }

    public function addSet(string $groupHandle, string $fieldsetHandle, array $set): void
    {
        $data = $this->read();

        $groups = Arr::get($data, 'fields.0.field.sets', []);
        $group =
            $groups[$groupHandle] ??
            throw new \RuntimeException("Group '{$groupHandle}' not found.");
        $existing = Arr::get($group, 'sets', []);
        $existing[$fieldsetHandle] = $set;

        ksort($existing, SORT_NATURAL | SORT_FLAG_CASE);
        $group['sets'] = $existing;
        $groups[$groupHandle] = $group;

        Arr::set($data, 'fields.0.field.sets', $groups);
        $this->write($data);
    }

    private function read(): array
    {
        $full = base_path($this->path);
        if (!$this->files->exists($full)) {
            throw new \RuntimeException("Missing fieldset file: {$this->path}");
        }
        return Yaml::parseFile($full) ?? [];
    }

    private function write(array $data): void
    {
        $yaml = Yaml::dump($data, 99, 2);
        $this->files->put(base_path($this->path), $yaml);
    }
}
