# [Bedrock demo](https://bedrock.remarkable.dev)

This repository has two jobs:

1. It is the live demo of the Bedrock starter kit at [bedrock.remarkable.dev](https://bedrock.remarkable.dev).
2. It is the source of the starter kit. The `bedrock:export-starter-kit` command exports the kit from this project to the [jasonbaciulis/bedrock](https://github.com/jasonbaciulis/bedrock) repository.

To start a new project, install the starter kit, not this repository:

```bash
statamic new project_name jasonbaciulis/bedrock
```

The starter kit documentation is in [`package/DOCUMENTATION.md`](package/DOCUMENTATION.md).

## Local setup

You need PHP 8.4 or later, Composer, [Bun](https://bun.sh/) and Redis (for Horizon). [Herd](https://herd.laravel.com) serves the site at [https://bedrock-demo.test](https://bedrock-demo.test).

1. Install dependencies, create `.env` and build the assets: `composer run setup`
2. Create a Statamic user: `php please make:user`
3. (Optional) Install the MCP server and AI coding guidelines: `php artisan boost:install`
4. Start Horizon, log tailing and Vite: `composer run dev`

Sign in to the control panel at [https://bedrock-demo.test/cp](https://bedrock-demo.test/cp).

## Checks

- `composer lint` - Fix code style with Rector, Pint, Prettier and ESLint.
- `composer test` - Run all checks: lint, type coverage, PHPStan, unit tests with 100% coverage, and browser tests.

## Demo only

These parts are in the demo, but not in the starter kit:

| Part | Files |
| --- | --- |
| Blog | `posts` collection, `categories` taxonomy, `blog_excerpt` and `blog_paginated` blocks, `posts/show` template, `entry-posts`, `entry-meta`, `social-sharing` and `related-posts` views, `strings` global |
| Search | `search_form` and `search_results` blocks, `search-form` component, Search and Results pages |
| Hero demo | `hero_demo` block |
| Contact form | `resources/forms/contact.yaml` and its blueprint (the kit's Contact block works with any form that you create) |
| Load more entries | `resources/js/fetch-entries.js` (needs the Statamic REST API). |
| Mega menu | `nav-header-item` partial and the `header` navigation blueprint (icon, description and badge fields) |

The demo pages label the blog and search as "Demo only".

## Starter kit export

The `package/` folder holds the starter kit:

- `starter-kit.yaml` - `export_paths` lists the files that the kit copies from this project.
- `export/` - Kit versions of files that hold demo values in this project, for example `blocks.yaml`, the header navigation and the home page. The export copies these files after the `export_paths`, so they replace the demo versions.
- `StarterKitPostInstall.php` - Installs the dev dependencies, Boost and the MCP server, and formats the new project.
- `README.md` and `DOCUMENTATION.md` - The kit's documentation.

To export the kit:

```bash
php artisan bedrock:export-starter-kit ../bedrock
```

The command replaces the previous export, writes the dev dependency versions for the post-install hook, and fails when a view or blueprint uses a partial or fieldset that the kit does not include.

When you add a file that the kit needs, add it to `export_paths`. When a file has demo values, add a kit version to `package/export/` instead.

After the export, open a pull request in the kit repository. Each published release runs the "Test Live Installation" workflow, which installs the kit with `statamic new` in a fresh site and runs the browser smoke tests.

## Deployment

The demo is a static site on Vercel. The `.github/workflows/deploy-vercel.yml` workflow runs on each push to `main`:

1. It builds the assets and generates the static site with [Statamic SSG](https://github.com/statamic/ssg) (`php please ssg:generate`).
2. It deploys `storage/app/static` to Vercel with `vercel deploy --prebuilt --prod`.

The workflow needs the `VERCEL_ORG_ID`, `VERCEL_PROJECT_ID` and `VERCEL_TOKEN` secrets and the `APP_URL` variable. `build.sh` stops Vercel's own Git builds, so that only the workflow deploys.

Know these limits of the static demo:

- Search, the contact form and the newsletter form need a PHP server. On the static demo, a search shows no results and the forms show an error.
- The workflow sets `BEDROCK_CP_TOOLBAR_ENABLED=false`. A static site has no signed-in users. Also, the toolbar's `nocache` region makes Statamic keep the `/404` URL as the pagination path, so pages generated after `/404` link to `/404/page/N`. The workflow fails when it finds such a link.
