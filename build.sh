#!/bin/sh
set -e

# Install PHP 8.3 (simple)
if command -v amazon-linux-extras >/dev/null 2>&1; then
    yum clean metadata -y || true
    amazon-linux-extras enable php8.3 || true
    yum install -y php php-cli php-common php-mbstring php-gd php-bcmath php-xml php-fpm php-intl php-zip
else
    dnf install -y php8.3 php8.3-{common,mbstring,gd,bcmath,xml,fpm,intl,zip} || true
fi

# INSTALL COMPOSER
EXPECTED_CHECKSUM="$(wget -q -O - https://composer.github.io/installer.sig)"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]
then
    >&2 echo 'ERROR: Invalid installer checksum'
    rm composer-setup.php
    exit 1
fi

php composer-setup.php --quiet
rm composer-setup.php

# INSTALL COMPOSER DEPENDENCIES
php composer.phar install

# GENERATE APP KEY
php artisan key:generate

# BUILD STATIC SITE
php please stache:warm -n -q
php please ssg:generate
