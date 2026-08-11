<?php

namespace App\Console\Commands\Scaffold;

use App\Support\Scaffold\ArticleSetTarget;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

#[Description('Delete a Statamic Article set.')]
#[Signature('delete:bedrock-set
        {group? : Group handle in Article}
        {set?   : Set (fieldset) handle to delete}
        {--keep-files : Only remove from article.yaml; keep fieldset/view files}
        {--force : Ignore missing files when deleting}')]
final class DeleteSet extends Command
{
    public function handle(Filesystem $files, ArticleSetTarget $target): int
    {
        return new DeleteScaffold($files, $target)->run(
            group: $this->argument('group'),
            handle: $this->argument('set'),
            keepFiles: (bool) $this->option('keep-files'),
            force: (bool) $this->option('force'),
        );
    }
}
