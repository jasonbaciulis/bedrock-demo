#!/bin/sh

# Update package list and install prerequisites
apt-get update -y
apt-get install -y wget gnupg2 software-properties-common

# Add PHP repository and install PHP 8.2
add-apt-repository ppa:ondrej/php -y
apt-get update -y
apt-get install -y php8.2 php8.2-common php8.2-curl php8.2-mbstring php8.2-gd php8.2-gettext php8.2-bcmath php8.2-json php8.2-xml php8.2-fpm php8.2-intl php8.2-zip php8.2-imap

# INSTALL COMPOSER
EXPECTED_CHECKSUM="$(wget -q -O - https://composer.github.io/installer.sig)"
php8.2 -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_CHECKSUM="$(php8.2 -r "echo hash_file('sha384', 'composer-setup.php');")"

if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]
then
    >&2 echo 'ERROR: Invalid installer checksum'
    rm composer-setup.php
    exit 1
fi

php8.2 composer-setup.php --quiet
rm composer-setup.php

# INSTALL COMPOSER DEPENDENCIES
php8.2 composer.phar install

# GENERATE APP KEY
php8.2 artisan key:generate

# BUILD STATIC SITE
php8.2 please ssg:generate
