#!/bin/sh

# Install WGET first (needed for Composer)
dnf install -y wget

# Install PHP 8.4 & extensions from Amazon Linux 2023 repos.
# Note: Remi RPM is not compatible with AL2023 (requires RHEL/CentOS-Stream 9).
dnf install -y php8.4 php8.4-{cli,common,mbstring,gd,bcmath,xml,fpm,intl,zip}

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
