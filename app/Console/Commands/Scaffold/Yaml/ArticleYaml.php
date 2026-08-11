<?php

declare(strict_types=1);

namespace App\Console\Commands\Scaffold\Yaml;

use Illuminate\Filesystem\Filesystem;

final class ArticleYaml extends GroupedSetsYaml
{
    public function __construct(Filesystem $files)
    {
        parent::__construct(
            $files,
            config('statamic.bedrock.scaffold.fieldsets_path').'/article.yaml',
            'article'
        );
    }
}
