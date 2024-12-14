#!/bin/sh

# Install PHP & WGET
yum install -y wget
yum install -y https://rpms.remirepo.net/enterprise/remi-release-8.rpm
yum module reset php -y
yum module enable php:remi-8.2 -y
yum install -y php php-common php-curl php-mbstring php-gd php-gettext php-bcmath php-json php-xml php-fpm php-intl php-zip php-imap

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
php please ssg:generate
