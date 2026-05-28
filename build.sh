#!/bin/sh

# Deploys are produced by .github/workflows/deploy.yml and pushed to Vercel
# via `vercel deploy --prebuilt`. If Vercel's git integration is still
# enabled and triggers a build, exit successfully without doing anything so
# the existing production deployment is preserved.
echo "Build is handled by GitHub Actions. No-op for Vercel git builds."
exit 0
