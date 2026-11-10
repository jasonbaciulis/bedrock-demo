#!/bin/bash

# Export Statamic Starter Kit Script
# This script cleans the ../bedrock directory, exports the starter kit and
# checks that the exported files do not refer to files that were not exported
# How to run: bash export-starter-kit.sh

set -e

echo "🚀 Starting starter kit export…"

# The post-install hook installs the dev dependencies, because Statamic cannot pass
# --with-all-dependencies. Thus the versions in the hook must match composer.json.
# This runs first so drift fails before ../bedrock is deleted.
echo "🔍 Checking the dev dependency versions in package/StarterKitPostInstall.php…"
php <<'PHP'
<?php
require 'package/StarterKitPostInstall.php';

$composer = json_decode(file_get_contents('composer.json'), true)['require-dev'] ?? [];
$problems = [];

foreach (StarterKitPostInstall::DEV_DEPENDENCIES as $package) {
    [$name, $version] = explode(':', $package, 2);
    $required = $composer[$name] ?? null;

    if ($required === $version) {
        continue;
    }

    $problems[] = $required === null
        ? "  ⚠️  {$name} is not in require-dev"
        : "  ⚠️  {$name}: hook has {$version}, composer.json has {$required}";
}

echo $problems
    ? implode(PHP_EOL, $problems).PHP_EOL."  Please update DEV_DEPENDENCIES in package/StarterKitPostInstall.php.".PHP_EOL
    : '  ✓ All '.count(StarterKitPostInstall::DEV_DEPENDENCIES)." versions match composer.json".PHP_EOL;

exit($problems ? 1 : 0);
PHP

KIT_DIR="$(cd ../bedrock && pwd)"

# Clean ../bedrock (preserving .git .claude and .github)
echo "📁 Cleaning ../bedrock directory…"
find "$KIT_DIR" -mindepth 1 -maxdepth 1 \
    ! -name '.git' ! -name '.claude' ! -name '.github' \
    -exec rm -rf {} +

# Export the starter kit
echo "📦 Exporting starter kit to ../bedrock…"
php please starter-kit:export "$KIT_DIR"

# export_paths lists single files, so a new view or fieldset can be left out by mistake.
echo "🔍 Checking that the exported partials and fieldsets exist…"
KIT_DIR="$KIT_DIR" php <<'PHP'
<?php
$kit = getenv('KIT_DIR').'/export';
$problems = [];

// Statamic tries `_name`, `partials/name` and `partials/_name` before `name`.
function partialExists(string $kit, string $name): bool
{
    $path = str_replace('.', '/', $name);
    $underscored = preg_replace('#([^/]+)$#', '_$1', $path);

    foreach ([$path, $underscored, "partials/{$path}", "partials/{$underscored}"] as $candidate) {
        foreach (['antlers.html', 'blade.php'] as $extension) {
            if (is_file("{$kit}/resources/views/{$candidate}.{$extension}")) {
                return true;
            }
        }
    }

    return false;
}

function filesIn(string $directory, string $pattern): Iterator
{
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));

    return new RegexIterator($files, $pattern);
}

foreach (filesIn("{$kit}/resources/views", '/\.(html|php)$/') as $view) {
    preg_match_all('/(?:<s:partial:|\{\{\s*partial:|partial\s+src=")([^\s>"}]+)/', file_get_contents($view), $matches);

    foreach (array_unique($matches[1]) as $partial) {
        // A name built from a variable, such as `blocks/{type}`, is only known at render time.
        if (! str_contains($partial, '{') && ! partialExists($kit, $partial)) {
            $problems[] = '  ⚠️  '.substr($view, strlen($kit) + 1)." includes the partial {$partial}, which was not exported";
        }
    }
}

foreach (['fieldsets', 'blueprints'] as $directory) {
    foreach (filesIn("{$kit}/resources/{$directory}", '/\.yaml$/') as $yaml) {
        preg_match_all('/^\s*(?:-\s*)?(import|field):\s*[\'"]?([\w.]+)[\'"]?\s*$/m', file_get_contents($yaml), $matches, PREG_SET_ORDER);

        foreach ($matches as [, $key, $reference]) {
            // `field: common.text_plain` refers to the `text_plain` field of the `common` fieldset.
            $fieldset = $key === 'import' ? $reference : substr($reference, 0, (int) strrpos($reference, '.'));

            if ($fieldset !== '' && ! is_file("{$kit}/resources/fieldsets/".str_replace('.', '/', $fieldset).'.yaml')) {
                $problems[] = '  ⚠️  '.substr($yaml, strlen($kit) + 1)." uses the fieldset {$fieldset}, which was not exported";
            }
        }
    }
}

echo $problems
    ? implode(PHP_EOL, array_unique($problems)).PHP_EOL.'  Please add the missing files to export_paths in package/starter-kit.yaml.'.PHP_EOL
    : '  ✓ All partials and fieldsets exist'.PHP_EOL;

exit($problems ? 1 : 0);
PHP
