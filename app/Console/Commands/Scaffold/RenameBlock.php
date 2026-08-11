<?php

namespace App\Console\Commands\Scaffold;

use App\Support\Scaffold\BlockTarget;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

#[Description('Rename a Statamic page builder block')]
#[Signature('rename:bedrock-block
        {group? : The group handle (e.g. hero)}
        {current_name? : The current block handle to rename}
        {new_name? : The new block display name}
        {--force : Overwrite existing files}')]
final class RenameBlock extends Command
{
    public function handle(Filesystem $files, BlockTarget $target): int
    {
        return new RenameScaffold($files, $target, namePlaceholder: 'e.g. Hero Screenshot')->run(
            group: $this->argument('group'),
            currentHandle: $this->argument('current_name'),
            newName: $this->argument('new_name'),
            force: (bool) $this->option('force'),
        );
    }
}
