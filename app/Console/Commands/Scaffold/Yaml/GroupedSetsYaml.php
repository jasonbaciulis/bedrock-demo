<?php

declare(strict_types=1);

namespace App\Console\Commands\Scaffold\Yaml;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use RuntimeException;
use Statamic\Facades\YAML;
use Stringy\StaticStringy as Stringy;

class GroupedSetsYaml
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly string $path,
        private readonly string $fieldHandle
    ) {}

    /**
     * @return array<string, string> Group handle to display label
     */
    public function groups(): array
    {
        $data = $this->read();
        $root = $this->groupsRoot($data);

        return $this->labelsFromConfig($root);
    }

    /**
     * @return array<string, string> Set handle to display label
     */
    public function sets(string $groupHandle): array
    {
        $data = $this->read();
        $root = $this->groupsRoot($data);

        if (! array_key_exists($groupHandle, $root)) {
            throw new RuntimeException("Group '{$groupHandle}' not found in {$this->path}.");
        }

        return $this->labelsFromConfig(Arr::get($root[$groupHandle], 'sets', []));
    }

    /**
     * @param  array<string, mixed>  $set
     */
    public function addSet(string $groupHandle, string $setHandle, array $set): void
    {
        $data = $this->read();

        throw_unless(isset($this->groupsRoot($data)[$groupHandle]), RuntimeException::class, "Group '{$groupHandle}' not found.");

        $this->write($this->withSet($data, $groupHandle, $setHandle, $set));
    }

    public function removeSet(string $groupHandle, string $setHandle): void
    {
        $data = $this->read();

        throw_unless(isset($this->groupsRoot($data)[$groupHandle]['sets'][$setHandle]), RuntimeException::class, "Set '{$setHandle}' not found in group '{$groupHandle}'.");

        $this->write($this->withoutSet($data, $groupHandle, $setHandle));
    }

    /**
     * Rename a set (optionally moving it to another group) in a single read-modify-write,
     * so a failure can never leave the set removed but not re-added.
     */
    public function renameSet(
        string $fromGroup,
        string $toGroup,
        string $oldHandle,
        string $newHandle,
        string $newDisplay
    ): void {
        $data = $this->read();
        $root = $this->groupsRoot($data);

        throw_unless(isset($root[$fromGroup]['sets'][$oldHandle]), RuntimeException::class, "Set '{$oldHandle}' not found in group '{$fromGroup}'.");
        throw_unless(isset($root[$toGroup]), RuntimeException::class, "Group '{$toGroup}' not found.");

        $set = $root[$fromGroup]['sets'][$oldHandle];
        $set['display'] = $newDisplay;
        if (isset($set['fields'][0]['import'])) {
            $set['fields'][0]['import'] = $newHandle;
        }

        $data = $this->withoutSet($data, $fromGroup, $oldHandle);
        $data = $this->withSet($data, $toGroup, $newHandle, $set);

        $this->write($data);
    }

    public function fileName(): string
    {
        return basename($this->path);
    }

    private function withSet(array $data, string $groupHandle, string $setHandle, array $set): array
    {
        $sets = collect(Arr::get($this->groupsRoot($data)[$groupHandle], 'sets', []))->put($setHandle, $set);

        return $this->updateGroupSets($data, $groupHandle, $this->sortKeysNaturally($sets->all()));
    }

    private function withoutSet(array $data, string $groupHandle, string $setHandle): array
    {
        $sets = collect(Arr::get($this->groupsRoot($data)[$groupHandle], 'sets', []))->except($setHandle);

        return $this->updateGroupSets($data, $groupHandle, $this->sortKeysNaturally($sets->all()));
    }

    private function groupsRoot(array $data): array
    {
        $index = $this->groupFieldIndexOrFail($data);

        return $data['fields'][$index]['field']['sets'] ?? [];
    }

    private function updateGroupSets(array $data, string $groupHandle, array $sets): array
    {
        $index = $this->groupFieldIndexOrFail($data);

        $data['fields'][$index]['field']['sets'][$groupHandle]['sets'] = $sets;

        return $data;
    }

    private function groupFieldIndexOrFail(array $data): int
    {
        if (! isset($data['fields']) || ! is_array($data['fields'])) {
            throw new RuntimeException(
                "Invalid YAML structure in {$this->path}: missing 'fields'."
            );
        }

        foreach ($data['fields'] as $index => $field) {
            if (($field['handle'] ?? null) === $this->fieldHandle) {
                return $index;
            }
        }

        throw new RuntimeException(
            "Field handle '{$this->fieldHandle}' not found in {$this->path}."
        );
    }

    private function sortKeysNaturally(array $items): array
    {
        return collect($items)
            ->sortKeysUsing(static fn (string $firstKey, string $secondKey): int => strnatcasecmp($firstKey, $secondKey))
            ->all();
    }

    private function labelsFromConfig(array $items): array
    {
        return collect($items)
            ->mapWithKeys(
                fn (array $config, string $handle): array => [
                    $handle => (string) ($config['display'] ?? Stringy::humanize($handle)),
                ]
            )
            ->all();
    }

    private function read(): array
    {
        if (! $this->files->exists($this->path)) {
            throw new RuntimeException("Missing fieldset file: {$this->path}");
        }

        return YAML::file($this->path)->parse();
    }

    private function write(array $data): void
    {
        $this->files->put($this->path, YAML::dump($data));
    }
}
