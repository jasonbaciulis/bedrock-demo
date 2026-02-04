#!/bin/sh

# Install WGET first (needed for Composer)
dnf install -y wget

# Install Remi repository for PHP 8.2
dnf install -y https://rpms.remirepo.net/enterprise/remi-release-9.rpm
dnf module reset php -y
dnf module enable php:remi-8.2 -y

# Install PHP 8.4 & extensions
dnf install -y php php-{common,mbstring,gd,bcmath,xml,fpm,intl,zip}

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
