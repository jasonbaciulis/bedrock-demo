#!/bin/sh
set -e

# Install required tools
dnf install -y wget tar xz

# Download prebuilt PHP 8.4 binary from shivammathur/php-builder
PHP_VERSION="8.4.4"
echo "Downloading PHP ${PHP_VERSION}..."
wget -O php.tar.xz "https://github.com/shivammathur/php-builder/releases/download/php-${PHP_VERSION}/php-${PHP_VERSION}-linux-x64.tar.xz"
mkdir -p php-bin
tar -xf php.tar.xz -C php-bin
PHP_BIN="$(pwd)/php-bin/bin/php"
chmod +x $PHP_BIN

# Verify PHP version
echo "PHP version:"
$PHP_BIN -v

# INSTALL COMPOSER
echo "Installing Composer..."
EXPECTED_CHECKSUM="$(wget -q -O - https://composer.github.io/installer.sig)"
$PHP_BIN -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_CHECKSUM="$($PHP_BIN -r "echo hash_file('sha384', 'composer-setup.php');")"

if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]
then
    >&2 echo 'ERROR: Invalid installer checksum'
    rm composer-setup.php
    exit 1
fi

$PHP_BIN composer-setup.php --quiet
rm composer-setup.php

# INSTALL COMPOSER DEPENDENCIES
echo "Installing Composer dependencies..."
$PHP_BIN composer.phar install --no-interaction --no-dev --optimize-autoloader

# GENERATE APP KEY
echo "Generating app key..."
$PHP_BIN artisan key:generate

# BUILD STATIC SITE
echo "Warming stache..."
$PHP_BIN please stache:warm -n -q
echo "Generating static site..."
$PHP_BIN please ssg:generate
