#!/bin/bash

# Export Statamic Starter Kit Script
# This script cleans the ../bedrock directory and exports the current starter kit
# How to run: bash export-starter-kit.sh

echo "🚀 Starting starter kit export…"

# Navigate to ../bedrock and clean it (preserving .git and .github)
echo "📁 Cleaning ../bedrock directory…"
cd ../bedrock && ls -A | grep -v '^\.git$' | grep -v '^\.github$' | xargs rm -rf

# Return to the original directory
cd -

# Export the starter kit
echo "📦 Exporting starter kit to ../bedrock…"
php please starter-kit:export ../bedrock
