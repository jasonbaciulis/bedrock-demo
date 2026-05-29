#!/usr/bin/env sh
set -e

rm -rf resources/svg/lucide
mkdir -p resources/svg/lucide
cp -R node_modules/lucide-static/icons/. resources/svg/lucide
