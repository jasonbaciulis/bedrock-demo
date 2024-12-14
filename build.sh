#!/bin/sh

# Update package list and install prerequisites
apk update
apk add --no-cache wget gnupg php8 php8-common php8-curl php8-mbstring php8-gd php8-gettext php8-bcmath php8-json php8-xml php8-fpm php8-intl php8-zip php8-imap

# INSTALL COMPOSER
EXPECTED_CHECKSUM="$(wget -q -O - https://composer.github.io/installer.sig)"
php8 -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_CHECKSUM="$(php8 -r "echo hash_file('sha384', 'composer-setup.php');")"

if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]
then
    >&2 echo 'ERROR: Invalid installer checksum'
    rm composer-setup.php
    exit 1
fi

php8 composer-setup.php --quiet
rm composer-setup.php

# INSTALL COMPOSER DEPENDENCIES
php8 composer.phar install

# GENERATE APP KEY
php8 artisan key:generate
