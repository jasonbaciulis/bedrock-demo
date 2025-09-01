<?php

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Laravel\Prompts\ConfirmPrompt;
use Laravel\Prompts\Prompt;
use Statamic\Facades\Entry as EntryFacade;
use Statamic\Facades\GlobalVariables;
use Statamic\Facades\Nav;
use Statamic\Facades\Site;
use Statamic\Facades\Term;

beforeAll(function () {
    // Auto-confirm prompts for destructive actions.
    Prompt::fallbackWhen(true);
    ConfirmPrompt::fallbackUsing(fn() => true);
});

beforeEach(function () {
    // Backup the entire content directory so we can restore it after the test.
    $this->fs = new Filesystem();
    $this->contentPath = base_path('content');
    $this->backupPath = base_path('content_backup_for_tests');

    if ($this->fs->exists($this->backupPath)) {
        $this->fs->deleteDirectory($this->backupPath);
    }
    $this->fs->copyDirectory($this->contentPath, $this->backupPath);
});

afterEach(function () {
    // Restore content directory from backup
    if (isset($this->fs) && $this->fs->exists($this->backupPath)) {
        $this->fs->deleteDirectory($this->contentPath);
        $this->fs->copyDirectory($this->backupPath, $this->contentPath);
        $this->fs->deleteDirectory($this->backupPath);
    }
});

test('bedrock:demo:clear removes demo content while preserving home entry', function () {
    // Ensure home exists; if not, create it.
    $home = EntryFacade::whereCollection('pages')->first(fn($entry) => $entry->slug() === 'home');
    if (!$home) {
        /** @var \Statamic\Entries\Entry $newHome */
        $newHome = EntryFacade::make();
        $newHome
            ->collection('pages')
            ->slug('home')
            ->data(['title' => 'Home']);
        $newHome->save();
        $home = EntryFacade::find($newHome->id());
    }

    // Ensure there's at least one other entry to delete
    if (EntryFacade::all()->count() <= 1) {
        /** @var \Statamic\Entries\Entry $tempPost */
        $tempPost = EntryFacade::make();
        $tempPost
            ->collection('posts')
            ->slug('temp-post')
            ->data(['title' => 'Temp Post']);
        $tempPost->save();
    }

    // Ensure at least one term exists in categories
    if (Term::whereTaxonomy('categories')->count() === 0) {
        /** @var \Statamic\Taxonomies\Term $tempTerm */
        $tempTerm = \Statamic\Facades\Term::make('temp-category');
        $tempTerm->taxonomy('categories')->set('title', 'Temp Category');
        $tempTerm->save();
    }

    // Ensure globals exist for specified sets across default site
    foreach (['banner', 'browser_appearance', 'newsletter', 'social_media', 'theme'] as $handle) {
        if (GlobalVariables::whereSet($handle)->count() === 0) {
            $vars = \Statamic\Facades\GlobalSet::findByHandle($handle)?->inDefaultSite();
            if ($vars) {
                $vars->set('seeded', true)->save();
            }
        }
    }

    // Ensure at least one nav has items in at least one site
    $sites = Site::all()->map->handle();
    foreach (Nav::all() as $nav) {
        foreach ($sites as $site) {
            $tree = $nav->in($site);
            if ($tree && empty($tree->tree())) {
                $tree->tree([['entry' => $home->id()]])->save();
            }
        }
    }

    // Run the command
    $this->artisan('bedrock:demo:clear', ['--force' => true])->assertExitCode(Command::SUCCESS);

    // 1) Entries: only home page should remain in pages; other collections should be empty
    $entries = EntryFacade::all();
    // Keep everything that is the home entry
    $nonHome = $entries->reject(function ($entry) use ($home) {
        if ($entry->id() === $home->id()) {
            return true;
        }

        return false;
    });
    expect($nonHome->count())->toBe(0);

    // Home must have fields cleared
    /** @var \Statamic\Entries\Entry $freshHome */
    $freshHome = EntryFacade::find($home->id());
    expect($freshHome->data()->get('blocks'))->toBeNull();
    expect($freshHome->data()->get('seo_title'))->toBeNull();
    expect($freshHome->data()->get('seo_description'))->toBeNull();
    expect($freshHome->data()->get('og_image'))->toBeNull();
    expect($freshHome->data()->get('twitter_image'))->toBeNull();

    // 2) Navs: all trees should be empty
    foreach (Nav::all() as $nav) {
        foreach (Site::all()->map->handle() as $site) {
            $tree = $nav->in($site);
            if ($tree) {
                expect($tree->tree())->toBe([]);
            }
        }
    }

    // 3) Terms: categories should be empty
    expect(Term::whereTaxonomy('categories')->count())->toBe(0);

    // 4) Globals: variables for selected sets should be deleted
    foreach (['banner', 'browser_appearance', 'newsletter', 'social_media', 'theme'] as $handle) {
        expect(GlobalVariables::whereSet($handle)->count())->toBe(0);
    }
});
