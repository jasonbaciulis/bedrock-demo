<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Support;

use Illuminate\Support\Facades\File;
use Statamic\Facades\Fieldset;

/**
 * The isolated workspace the console command tests run against, so the commands
 * mutate copies instead of the real kit.
 */
final class Scratch
{
    public static function path(): string
    {
        return storage_path('framework/testing/bedrock-'.ParallelWorker::id());
    }

    /**
     * Fieldsets directory the Statamic fieldset repository points at during
     * scaffold tests.
     */
    public static function fieldsetsPath(): string
    {
        return self::path().'/fieldsets';
    }

    /**
     * Provision an isolated scaffold workspace (fieldsets + views), point the
     * Statamic fieldset repository at it, and rebind the
     * statamic.bedrock.scaffold.* config so the commands under test write into
     * it. The real fieldsets are copied in as seeds so fieldset imports keep
     * resolving.
     */
    public static function setUpScaffoldWorkspace(): void
    {
        $blocksViews = self::path().'/views/blocks';
        $setsViews = self::path().'/views/sets';

        File::ensureDirectoryExists(self::fieldsetsPath());
        File::ensureDirectoryExists($blocksViews);
        File::ensureDirectoryExists($setsViews);

        File::copyDirectory(resource_path('fieldsets'), self::fieldsetsPath());

        Fieldset::setDirectory(self::fieldsetsPath());

        config([
            'statamic.bedrock.scaffold.blocks_views_path' => $blocksViews,
            'statamic.bedrock.scaffold.sets_views_path' => $setsViews,
        ]);
    }

    public static function delete(): void
    {
        File::deleteDirectory(self::path());
    }
}
