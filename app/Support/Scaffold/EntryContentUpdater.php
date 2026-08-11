<?php

namespace App\Support\Scaffold;

use Closure;
use Illuminate\Support\Collection;
use Statamic\Facades\Entry;

/**
 * Renames, deletes, and counts usages of a scaffold target's sets inside entry content.
 */
final class EntryContentUpdater
{
    public function __construct(private readonly ScaffoldTarget $target) {}

    /**
     * @return Collection<int, \Statamic\Contracts\Entries\Entry>
     */
    public function entriesUsing(string $fieldset): Collection
    {
        $matches = $this->target->usageMatcher($fieldset);

        return Entry::all()
            ->filter(fn ($entry): bool => $this->fieldItems($entry)->contains($matches))
            ->values();
    }

    /**
     * @return int Number of entries updated
     */
    public function renameUsages(string $oldHandle, string $newHandle): int
    {
        $matches = $this->target->usageMatcher($oldHandle);
        $rename = $this->target->usageRenamer($newHandle);

        return $this->updateEntries(
            $this->entriesUsing($oldHandle),
            fn (Collection $items): Collection => $items->map(
                fn ($item) => $matches($item) ? $rename($item) : $item
            )
        );
    }

    /**
     * @param  Collection<int, \Statamic\Contracts\Entries\Entry>  $entries  Entries from entriesUsing()
     * @return int Number of entries updated
     */
    public function deleteUsagesIn(Collection $entries, string $fieldset): int
    {
        $matches = $this->target->usageMatcher($fieldset);

        return $this->updateEntries(
            $entries,
            fn (Collection $items): Collection => $items->reject($matches)->values()
        );
    }

    private function updateEntries(Collection $entries, Closure $transform): int
    {
        return $entries
            ->each(function ($entry) use ($transform): void {
                $entry->set($this->target->entryField(), $transform($this->fieldItems($entry))->all());
                $entry->save();
            })
            ->count();
    }

    private function fieldItems($entry): Collection
    {
        return collect((array) $entry->get($this->target->entryField()));
    }
}
