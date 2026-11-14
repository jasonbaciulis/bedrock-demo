<?php

declare(strict_types=1);

namespace App\Actions\StarterKit;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Statamic\Facades\YAML;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

final readonly class FindMissingExportPaths
{
    /**
     * The installer copies only `export_paths`, so a kit version of a file in package/export must also be listed there.
     *
     * @return list<string>
     */
    public function handle(string $kitPath): array
    {
        /** @var list<string> $exportPaths */
        $exportPaths = Arr::array(YAML::parse(File::get("{$kitPath}/starter-kit.yaml")), 'export_paths');

        $unlistedFiles = array_filter(
            $this->exportedFilesIn($kitPath),
            fn (string $file): bool => ! $this->isInExportPaths($file, $exportPaths),
        );

        return array_values(array_map(
            fn (string $file): string => "{$file} is in the kit, but not in export_paths, so the installer skips it",
            $unlistedFiles,
        ));
    }

    /**
     * @return list<string>
     */
    private function exportedFilesIn(string $kitPath): array
    {
        $files = Finder::create()->files()->in("{$kitPath}/export")->ignoreDotFiles(false)->sortByName();

        return array_map(
            fn (SplFileInfo $file): string => $file->getRelativePathname(),
            iterator_to_array($files, false),
        );
    }

    /**
     * @param  list<string>  $exportPaths
     */
    private function isInExportPaths(string $file, array $exportPaths): bool
    {
        return array_any(
            $exportPaths,
            fn (string $exportPath): bool => $file === $exportPath || Str::startsWith($file, "{$exportPath}/"),
        );
    }
}
