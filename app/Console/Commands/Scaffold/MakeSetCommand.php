<?php

declare(strict_types=1);

namespace App\Console\Commands\Scaffold;

use App\Console\Commands\Scaffold\Actions\MakeScaffold;
use App\Console\Commands\Scaffold\Targets\ArticleSetTarget;
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
final class MakeSetCommand extends Command
{
    public function handle(Filesystem $files, ArticleSetTarget $target): int
    {
        return new MakeScaffold($files, $target, namePlaceholder: 'e.g. Gallery')->handle(
            group: $this->argument('group'),
            name: $this->argument('name'),
            instructions: $this->option('instructions'),
            force: (bool) $this->option('force'),
        );
    }
}
