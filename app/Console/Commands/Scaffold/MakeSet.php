<?php

namespace App\Console\Commands\Scaffold;

use App\Support\Scaffold\ArticleSetTarget;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

#[Description('Create a new Statamic Article set.')]
#[Signature('make:bedrock-set
        {group? : Group handle in Article}
        {name?  : Set display name}
        {--instructions= : Editor instructions}
        {--force : Overwrite existing files}')]
final class MakeSet extends Command
{
    public function handle(Filesystem $files, ArticleSetTarget $target): int
    {
        return new MakeScaffold($files, $target, namePlaceholder: 'e.g. Gallery')->run(
            group: $this->argument('group'),
            name: $this->argument('name'),
            instructions: $this->option('instructions'),
            force: (bool) $this->option('force'),
        );
    }
}
