<?php

namespace App\Support\Statamic;

use Illuminate\Filesystem\Filesystem;
use Statamic\Support\Arr;
use Stringy\StaticStringy as Stringy;
use Symfony\Component\Yaml\Yaml;
// use Statamic\Yaml\Yaml;

class GroupedSetsYaml
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly string $path,
        private readonly string $fieldHandle
    ) {}

    public function groups(): array
    {
        $data = $this->read();
        $root = $this->setsRoot($data);

        $out = [];
        foreach ($root as $handle => $group) {
            $out[$handle] = (string) ($group['display'] ?? Stringy::humanize($handle));
        }
        return $out;
    }

    public function sets(string $groupHandle): array
    {
        $data = $this->read();
        $root = $this->setsRoot($data);

        if (!array_key_exists($groupHandle, $root)) {
            throw new \RuntimeException("Group '{$groupHandle}' not found in {$this->path}.");
        }

        $pairs = [];
        foreach (Arr::get($root[$groupHandle], 'sets', []) as $handle => $config) {
            $pairs[$handle] = (string) ($config['display'] ?? Stringy::humanize($handle));
        }
        return $pairs;
    }

    public function addSet(string $groupHandle, string $setHandle, array $set): void
    {
        $data = $this->read();
        $root = &$this->setsRoot($data);
        $group =
            $root[$groupHandle] ?? throw new \RuntimeException("Group '{$groupHandle}' not found.");

        $sets = Arr::get($group, 'sets', []);
        $sets[$setHandle] = $set;
        ksort($sets, SORT_NATURAL | SORT_FLAG_CASE);

        $group['sets'] = $sets;
        $root[$groupHandle] = $group;

        $this->write($data);
    }

    public function removeSet(string $groupHandle, string $setHandle): void
    {
        $data = $this->read();
        $root = &$this->setsRoot($data);

        if (!isset($root[$groupHandle]['sets'][$setHandle])) {
            throw new \RuntimeException("Set '{$setHandle}' not found in group '{$groupHandle}'.");
        }

        unset($root[$groupHandle]['sets'][$setHandle]);

        $sets = $root[$groupHandle]['sets'] ?? [];
        ksort($sets, SORT_NATURAL | SORT_FLAG_CASE);
        $root[$groupHandle]['sets'] = $sets;

        $this->write($data);
    }

    private function &setsRoot(array &$data): array
    {
        $fields = Arr::get($data, 'fields', []);
        foreach ($fields as &$field) {
            if (($field['handle'] ?? null) === $this->fieldHandle) {
                $sets = &$field['field']['sets'];
                return $sets;
            }
        }
        throw new \RuntimeException(
            "Field handle '{$this->fieldHandle}' not found in {$this->path}."
        );
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
        $this->files->put(base_path($this->path), Yaml::dump($data, 99, 2));
    }
}
