# [Bedrock demo](https://bedrock.remarkable.dev)

## Requirements

Before running Statamic, you should ensure that your local machine has PHP and [Composer](https://getcomposer.org/) installed. If you are developing on macOS, PHP and Composer can be installed via [Homebrew](https://brew.sh/). In addition, I recommend [installing Bun](https://bun.sh/) for frontend dependencies.

## Installation

Using the [Statamic CLI](https://github.com/statamic/cli) tool run the following command:

```bash
statamic new project_name jasonbaciulis/bedrock
```

1. Navigate to the installed project folder: `cd project_name`
2. Install frontend dependencies: `bun install`
3. Create a Statamic user: `php please make:user`
4. (Optional) install the MCP server and AI coding guidelines: `php artisan boost:install`
5. Start Laravel's local dev server, bun run dev, start queue: `composer run dev`

Recommended to use [Herd](https://laravel.com/docs/13.x/installation#installation-using-herd) for local dev.
Once you have started the development server with `composer run dev`, the website will be accessible in your web browser at [https://bedrock.test](https://bedrock.test). Head to [https://bedrock.test/cp](https://bedrock.test/cp) and use your email address and password to sign into the Statamic control panel.

## Tools, libraries and addons used in the project
- [Statamic](https://statamic.dev/) - Flat file Laravel CMS.
- [TailwindCSS](https://tailwindcss.com/docs/installation) - Utility-first CSS framework.
- [Alpine.js](https://alpinejs.dev/start-here) - Rugged, minimal tool for composing behavior directly in your markup.
- [Embla](https://www.embla-carousel.com) - A lightweight carousel library with fluid motion and great swipe precision.
- [Laravel Boost](https://github.com/laravel/boost) - Accelerates AI-assisted development by providing the essential context that AI needs to generate high-quality, Laravel-specific code.
- [Statamic MCP](https://statamic.com/addons/cboxdk/statamic-mcp) - MCP (Model Context Protocol) server for Statamic that provides AI assistants with structured access to Statamic's capabilities through a modern router-based architecture.

## Views folder structure
```
resources/views/
├── blocks/                      # Page building blocks (Replicator fields)
├── components/                  # Project specific reusable components
│   └── ui/                      # Highly-reusable shadcn/ui style Alpine.js components
│       └── form/                # Statamic form (create), field wrapper and shared form parts (label, error, instructions, etc.)
│           └── fields/          # Form field types (custom Statamic form fields will require a snake_case view file here)
├── partials/                    # Template partials and fragments (things that aren't really reusable go here)
├── posts/                       # Posts collection templates
├── sets/                        # Content sets for Article Block
└── errors/                      # Error page templates
```

## Tailwind CSS config
- `config.css` - site's config. This file would typically include custom config for the project.
- `typography.css` - the Tailwind CSS typography config for customizing the prose class.

## CLI commands
There is a nice list of custom scripts available in the command line to make a developer's job easier and more enjoyable.

### Blocks
Blocks are like LEGO bricks that provide you the maximum flexibility when building pages. Blocks are based on [Replicator Fieldtype](https://statamic.dev/fieldtypes/replicator).

- `php please make:bedrock-block` - Adds a set to the Blocks field.
- `php please delete:bedrock-block` - Removes a set from the Blocks field and all associated files.
- `php please rename:bedrock-block` - Renames a set inside Blocks and optionally moves to another group.

### Sets
Sets provide a powerful content creation experience with unparalleled flexibility for building entries i.e., Blog posts. Sets live in the the Article field which is based on [Bard Fieldtype](https://statamic.dev/fieldtypes/bard).

- `php please make:bedrock-set` - adds a set to the Article.
- `php please delete:bedrock-set` - removes a set from the Article and all associated files.
- `php please rename:bedrock-set` - renames a set in the Article and optionally moves to another group.

## Production env file contents
Dump your `.env` values here with sensitive data removed. The following is a production example that uses full static caching:

```env
APP_NAME=Bedrock
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=

APP_LOCALE=en
APP_FALLBACK_LOCALE=en

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=sqlite

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis

CACHE_STORE=file

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

STATAMIC_LICENSE_KEY=
STATAMIC_PRO_ENABLED=true
STATAMIC_STACHE_WATCHER=false
STATAMIC_STATIC_CACHING_STRATEGY=full
STATAMIC_STATIC_WARM_INSECURE=true
STATAMIC_TWO_FACTOR_ENABLED=true
STATAMIC_REVISIONS_ENABLED=true
STATAMIC_GRAPHQL_ENABLED=false
STATAMIC_API_ENABLED=false
STATAMIC_GIT_ENABLED=true
STATAMIC_GIT_PUSH=true
STATAMIC_GIT_DISPATCH_DELAY=1

STATAMIC_CUSTOM_CMS_NAME="${APP_NAME}"
STATAMIC_CUSTOM_NAV_LOGO_URL='/assets/logos/logo-brand-fixed-white.svg'
STATAMIC_CUSTOM_OUTSIDE_LOGO_URL='/assets/logos/logo-brand-fixed.svg'

BEDROCK_CP_TOOLBAR_ENABLED=true
```

## NGINX config
When using full static caching strategy you will need to [configure server rewrite rules](https://statamic.dev/static-caching#nginx).

To enable resource caching add the following to your NGINX config __inside the server block__:
```
expires $expires;
```

And this __outside the server block__:
```
map $sent_http_content_type $expires {
    default    off;
    text/css    max;
    ~image/    max;
    font/woff2    max;
    application/javascript    max;
    application/octet-stream    max;
}
```

## Ploi deploy scripts (with zero-downtime enabled)

Ploi does not install Bun by default. [Install Bun on your server](https://ploi.io/documentation/server/how-to-install-bun-package-manager) before the first deploy.

### Pre deploy script

```bash
# Needed for Bun to work
export PATH="$HOME/.bun/bin:$PATH"
```

### Main deploy script

```bash
# Early Exit Check
if [[ {COMMIT_MESSAGE} =~ "[BOT]" ]]; then
    echo "Auto-committed on production. Nothing to deploy."
    {DO_NOT_NOTIFY}
    {CLEAR_NEW_RELEASE}
    exit 0
fi

cd {RELEASE}

# Git & Dependencies
git pull --rebase --autostash origin {BRANCH}

{SITE_COMPOSER} install --no-interaction --prefer-dist --optimize-autoloader --no-dev --classmap-authoritative

# Database (Safe Migration)
if [ -f artisan ]; then
    {SITE_PHP} artisan migrate --force
fi

# Frontend Build
bun install --frozen-lockfile
bun run build

# Laravel Optimization
# We group these to minimize the window where the app is "naked"
{SITE_PHP} artisan config:cache
{SITE_PHP} artisan route:cache
{SITE_PHP} artisan view:cache
{SITE_PHP} artisan event:cache

echo "⏳ Release prepared. Awaiting symlink switch…"
```

### Post deploy script

```bash
# Flush the PHP FPM worker
{RELOAD_PHP_FPM}

# Statamic Stache
{SITE_PHP} artisan statamic:stache:clear
{SITE_PHP} artisan statamic:stache:warm
{SITE_PHP} artisan statamic:stache:doctor

# Restart Queue Workers
{SITE_PHP} artisan queue:restart

# Update all search indexes
{SITE_PHP} artisan statamic:search:update --all

# Static Caching
{SITE_PHP} artisan statamic:static:clear
{SITE_PHP} artisan statamic:static:warm --queue

echo "🚀 Website deployed!"
```
