<?php

declare(strict_types=1);

namespace App\Console\Commands\Scaffold;

use App\Console\Commands\Scaffold\Actions\DeleteScaffold;
use App\Console\Commands\Scaffold\Targets\ArticleSetTarget;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

#[Description('Delete a Statamic Article set.')]
#[Signature('delete:bedrock-set
        {group? : Group handle in Article}
        {set?   : Set (fieldset) handle to delete}
        {--keep-files : Only remove from article.yaml; keep fieldset/view files}
        {--force : Skip confirmation and ignore missing files}')]
final class DeleteSetCommand extends Command
{
    public function handle(Filesystem $files, ArticleSetTarget $target): int
    {
        return new DeleteScaffold($files, $target)->handle(
            group: $this->argument('group'),
            fieldset: $this->argument('set'),
            keepFiles: (bool) $this->option('keep-files'),
            force: (bool) $this->option('force'),
        );
    }
}
