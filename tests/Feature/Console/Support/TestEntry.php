<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\Entry as EntryFacade;

/**
 * Entries the console tests seed into the shared content tree, because the
 * commands under test read it through the Stache.
 */
final class TestEntry
{
    /**
     * The id doubles as the slug, so Statamic's filename matches the
     * deleteAll() glob.
     *
     * @param  array<string, mixed>  $data
     */
    public static function create(string $collection, array $data): Entry
    {
        $id = 'test-'.Str::singular($collection).'-w'.ParallelWorker::id().'-'.Str::random(6);

        $entry = EntryFacade::make();
        $entry->collection($collection);
        $entry->id($id);
        $entry->slug($id);
        $entry->data($data);
        $entry->save();

        return $entry;
    }

    /**
     * Only this process's entries, so a parallel run never deletes the fixtures
     * another process is still using.
     */
    public static function deleteAll(): void
    {
        File::delete(File::glob(base_path('content/collections/*/test-*-w'.ParallelWorker::id().'-*.md')));
    }
}
