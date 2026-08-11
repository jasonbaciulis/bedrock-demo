<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Statamic\Entries\Entry;
use Statamic\Facades\Entry as EntryFacade;
use Statamic\Facades\Fieldset;
use Statamic\Facades\Stache;
use Statamic\Facades\YAML;
use Statamic\Fields\Fieldset as FieldsetInstance;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

#[Description('Remove the built-in SEO implementation so an SEO addon (e.g. seo-pro) can be installed on a blank slate.')]
#[Signature('bedrock:remove-seo {--force : Run without confirmation}')]
final class RemoveSeoCommand extends Command
{
    /**
     * The SEO fieldsets imported into collection blueprints.
     *
     * @var list<string>
     */
    private const array SEO_FIELDSETS = [
        'seo_basic',
        'seo_advanced',
        'seo_open_graph',
        'seo_json-ld_schema',
        'seo_sitemap',
    ];

    public function __construct(private readonly Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! $this->confirmedRemoval()) {
            info('Aborted.');

            return self::SUCCESS;
        }

        $this->stripSeoFieldsFromEntries($this->seoFieldHandles());
        $this->deleteSeoFiles();
        $this->removeSeoTabFromBlueprints();
        $this->cleanUpTemplates();
        $this->removeCookieDialogFromVite();
        $this->clearStache();
        $this->printNextSteps();

        info('SEO implementation removed.');

        return self::SUCCESS;
    }

    private function confirmedRemoval(): bool
    {
        if ($this->option('force')) {
            return true;
        }

        return confirm(
            label: 'This removes the built-in SEO global, fieldsets, templates and trackers. Continue?',
            default: false
        );
    }

    /**
     * Collect every field handle defined across the SEO fieldsets so the same
     * keys can be pruned from existing entries.
     *
     * @return list<string>
     */
    private function seoFieldHandles(): array
    {
        return $this->seoFieldsets()
            ->flatMap(fn (FieldsetInstance $fieldset): array => Arr::get($fieldset->contents(), 'fields', []))
            ->pluck('handle')
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $handles
     */
    private function stripSeoFieldsFromEntries(array $handles): void
    {
        if ($handles === []) {
            return;
        }

        $stripped = EntryFacade::all()
            ->filter(fn (Entry $entry): bool => $this->hasAnyHandle($entry, $handles))
            ->each(function (Entry $entry) use ($handles): void {
                $this->stripHandlesFromEntry($entry, $handles);
                $entry->save();
            })
            ->count();

        if ($stripped > 0) {
            info("Stripped SEO fields from {$stripped} ".Str::plural('entry', $stripped).'.');
        }
    }

    /**
     * @param  list<string>  $handles
     */
    private function hasAnyHandle(Entry $entry, array $handles): bool
    {
        return collect($handles)->contains(fn (string $handle): bool => $entry->has($handle));
    }

    /**
     * @param  list<string>  $handles
     */
    private function stripHandlesFromEntry(Entry $entry, array $handles): void
    {
        collect($handles)
            ->filter(fn (string $handle): bool => $entry->has($handle))
            ->each(fn (string $handle) => $entry->remove($handle));
    }

    private function deleteSeoFiles(): void
    {
        $this->seoFieldsets()->each(fn (FieldsetInstance $fieldset) => $fieldset->deleteQuietly());

        $paths = collect([
            $this->resourcePath('blueprints/globals/seo.yaml'),
            $this->contentPath('globals/seo.yaml'),
            $this->resourcePath('views/partials/seo.antlers.html'),
            $this->resourcePath('views/partials/fallback-description.antlers.html'),
            $this->resourcePath('views/partials/cookie-dialog.antlers.html'),
            $this->resourcePath('js/components/cookieDialog.js'),
        ])->merge($this->files->glob($this->contentPath('globals/*/seo.yaml')));

        $this->files->delete($paths->all());
    }

    /**
     * @return Collection<int, FieldsetInstance>
     */
    private function seoFieldsets(): Collection
    {
        return collect(self::SEO_FIELDSETS)
            ->map(fn (string $handle): ?FieldsetInstance => Fieldset::find($handle))
            ->filter()
            ->values();
    }

    private function removeSeoTabFromBlueprints(): void
    {
        collect($this->files->glob($this->resourcePath('blueprints/collections/*/*.yaml')))
            ->each(function (string $path): void {
                $data = YAML::file($path)->parse();

                if (! Arr::has($data, 'tabs.seo')) {
                    return;
                }

                Arr::forget($data, 'tabs.seo');
                $this->files->put($path, YAML::dump($data));
            });
    }

    private function cleanUpTemplates(): void
    {
        $this->replaceInFile(
            $this->resourcePath('views/layout.antlers.html'),
            [
                '<s:partial:partials.seo />' => '{{# Add your SEO addon meta tag here, e.g. {{ seo_pro:meta }} #}}',
                "        {{ yield:seo_body }}\n" => '',
            ]
        );

        $this->replaceInFile(
            $this->resourcePath('views/partials/nav-bottom-footer.antlers.html'),
            [
                "        {{# Let's users reset their cookies consent when using the cookie banner. #}}\n        {{ yield:reset_cookie_consent }}\n" => '',
            ]
        );

        $this->replaceInFile(
            $this->resourcePath('views/partials/social-sharing.antlers.html'),
            ['&via={{ seo:twitter_site }}' => ''],
        );
    }

    private function removeCookieDialogFromVite(): void
    {
        $this->replaceInFile(
            base_path('vite.config.js'),
            ["          'resources/js/components/cookieDialog.js',\n" => ''],
        );
    }

    /**
     * Warns for every needle that finds nothing, so drift between these exact
     * strings and reformatted templates surfaces instead of failing silently.
     *
     * @param  array<string, string>  $replacements
     */
    private function replaceInFile(string $path, array $replacements): void
    {
        if (! $this->files->exists($path)) {
            warning("Skipped missing file: {$path}");

            return;
        }

        $original = $this->files->get($path);

        $contents = collect($replacements)->reduce(
            function (string $contents, string $replacement, string $search) use ($path): string {
                if (! Str::contains($contents, $search)) {
                    warning("No match for '".Str::limit(trim($search), 60)."' in {$path}; remove it manually.");

                    return $contents;
                }

                return Str::replace($search, $replacement, $contents);
            },
            $original
        );

        if ($contents !== $original) {
            $this->files->put($path, $contents);
        }
    }

    private function clearStache(): void
    {
        Stache::clear();
        info('Cleared the Stache.');
    }

    private function printNextSteps(): void
    {
        warning('The cookie-consent dialog and analytics trackers were removed; re-add them separately if needed.');
        info('Next: install your SEO addon (e.g. `composer require statamic/seo-pro`) and add its meta tag in layout.antlers.html where the SEO partial used to be.');
    }

    private function resourcePath(string $relative): string
    {
        return base_path("resources/{$relative}");
    }

    private function contentPath(string $relative): string
    {
        return base_path("content/{$relative}");
    }
}
