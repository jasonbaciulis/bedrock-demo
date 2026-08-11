<?php

declare(strict_types=1);

namespace App\Console\Commands\Scaffold;

use App\Console\Commands\Scaffold\Actions\DeleteScaffold;
use App\Console\Commands\Scaffold\Targets\BlockTarget;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

#[Description('Delete a Statamic page builder block.')]
#[Signature('delete:bedrock-block
        {group? : The group handle (e.g. hero)}
        {block? : The block (fieldset) handle to delete}
        {--keep-files : Only remove from blocks.yaml; keep fieldset/view files}
        {--force : Skip confirmation and ignore missing files}')]
final class DeleteBlockCommand extends Command
{
    public function handle(Filesystem $files, BlockTarget $target): int
    {
        return new DeleteScaffold($files, $target)->handle(
            group: $this->argument('group'),
            fieldset: $this->argument('block'),
            keepFiles: (bool) $this->option('keep-files'),
            force: (bool) $this->option('force'),
        );
    }
}
