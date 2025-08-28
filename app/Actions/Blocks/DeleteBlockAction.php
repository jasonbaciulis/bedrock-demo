<?php

namespace App\Actions\Blocks;

use Illuminate\Filesystem\Filesystem;
use App\Support\Statamic\BlocksYaml;

class DeleteBlockAction
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly BlocksYaml $blocks
    ) {}

    public function __invoke(
        string $group,
        string $fieldset,
        bool $keepFiles = false,
        bool $force = false
    ): void {
        // 1) Remove from blocks.yaml first (source of truth)
        $this->blocks->removeSet($group, $fieldset);

        if ($keepFiles) {
            return;
        }

        // 2) Then delete the files (if present)
        $fieldsetPath = base_path("resources/fieldsets/{$fieldset}.yaml");
        $view = str_replace('_', '-', $fieldset);
        $viewPath = base_path("resources/views/blocks/{$view}.blade.php");

        $missing = [];

        if ($this->files->exists($fieldsetPath)) {
            $this->files->delete($fieldsetPath); // returns bool
        } else {
            $missing[] = $fieldsetPath;
        }

        if ($this->files->exists($viewPath)) {
            $this->files->delete($viewPath);
        } else {
            $missing[] = $viewPath;
        }

        if ($missing && !$force) {
            $list = implode("\n - ", $missing);
            throw new \RuntimeException(
                "Some files were not found to delete:\n - {$list}\n(Use --force to ignore.)"
            );
        }
    }
}
