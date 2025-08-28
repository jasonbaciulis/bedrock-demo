<?php

namespace App\Actions\Article;

use Illuminate\Filesystem\Filesystem;
use App\Support\Statamic\ArticleYaml;

class DeleteArticleSetAction
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly ArticleYaml $article
    ) {}

    public function __invoke(
        string $group,
        string $fieldset,
        bool $keepFiles = false,
        bool $force = false
    ): void {
        $this->article->removeSet($group, $fieldset);

        if ($keepFiles) {
            return;
        }

        $fieldsetPath = base_path("resources/fieldsets/{$fieldset}.yaml");
        $view = str_replace('_', '-', $fieldset);
        $viewPath = base_path("resources/views/sets/{$view}.blade.php");

        $missing = [];

        foreach ([$fieldsetPath, $viewPath] as $p) {
            if ($this->files->exists($p)) {
                $this->files->delete($p);
            } elseif (!$force) {
                $missing[] = $p;
            }
        }

        if ($missing && !$force) {
            $list = implode("\n - ", $missing);
            throw new \RuntimeException(
                "Some files were not found:\n - {$list}\n(Use --force to ignore.)"
            );
        }
    }
}
