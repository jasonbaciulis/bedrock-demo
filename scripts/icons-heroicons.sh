#!/usr/bin/env sh
set -e

rm -rf resources/svg/heroicons
mkdir -p resources/svg/heroicons
cp -R node_modules/heroicons/24/outline resources/svg/heroicons/outline
cp -R node_modules/heroicons/24/solid resources/svg/heroicons/solid
cp -R node_modules/heroicons/20/solid resources/svg/heroicons/mini
