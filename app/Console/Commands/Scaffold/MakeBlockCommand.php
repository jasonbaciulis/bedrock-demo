<?php

declare(strict_types=1);

namespace App\Console\Commands\Scaffold;

use App\Console\Commands\Scaffold\Actions\MakeScaffold;
use App\Console\Commands\Scaffold\Targets\BlockTarget;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

#[Description('Create a new Statamic page builder block')]
#[Signature('make:bedrock-block
        {group? : The group handle (e.g. hero)}
        {name? : The block display name}
        {--instructions= : Editor instructions}
        {--force : Overwrite existing files}')]
final class MakeBlockCommand extends Command
{
    public function handle(Filesystem $files, BlockTarget $target): int
    {
        return new MakeScaffold($files, $target, namePlaceholder: 'e.g. Hero Simple')->handle(
            group: $this->argument('group'),
            name: $this->argument('name'),
            instructions: $this->option('instructions'),
            force: (bool) $this->option('force'),
        );
    }
}
