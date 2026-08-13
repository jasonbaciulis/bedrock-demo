#!/bin/bash

# Export Statamic Starter Kit Script
# This script cleans the ../bedrock directory and exports the current starter kit
# How to run: bash export-starter-kit.sh

echo "🚀 Starting starter kit export…"

# The post-install hook installs the dev dependencies, because Statamic cannot pass
# --with-all-dependencies. Thus the versions in the hook must match composer.json.
# This runs first so drift fails before ../bedrock is deleted.
echo "🔍 Checking the dev dependency versions in package/StarterKitPostInstall.php…"
php <<'PHP' || exit 1
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

# Navigate to ../bedrock and clean it (preserving .git .claude and .github)
echo "📁 Cleaning ../bedrock directory…"
cd ../bedrock && find . -mindepth 1 -maxdepth 1 \
    ! -name '.git' ! -name '.claude' ! -name '.github' \
    -exec rm -rf {} +

# Return to the original directory
cd -

# Export the starter kit
echo "📦 Exporting starter kit to ../bedrock…"
php please starter-kit:export ../bedrock
