<?php

namespace App\Console\Commands\Scaffold;

use App\Support\Scaffold\ArticleSetTarget;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

#[Description('Rename a Statamic Article set')]
#[Signature('rename:bedrock-set
        {group? : The group handle in Article}
        {current_name? : The current set handle to rename}
        {new_name? : The new set display name}
        {--force : Overwrite existing files}')]
final class RenameSet extends Command
{
    public function handle(Filesystem $files, ArticleSetTarget $target): int
    {
        return new RenameScaffold($files, $target, namePlaceholder: 'e.g. Gallery Large')->run(
            group: $this->argument('group'),
            currentHandle: $this->argument('current_name'),
            newName: $this->argument('new_name'),
            force: (bool) $this->option('force'),
        );
    }
}
