#!/bin/sh

# Install required tools
dnf install -y wget unzip

# Download static PHP 8.4 binary
PHP_VERSION="8.4.4"
wget -q https://github.com/crazywhalecc/static-php-cli/releases/download/${PHP_VERSION}/php-${PHP_VERSION}-cli-linux-x86_64.tar.gz
tar -xzf php-${PHP_VERSION}-cli-linux-x86_64.tar.gz
chmod +x php
PHP_BIN="$(pwd)/php"

# Verify PHP version
$PHP_BIN -v

# INSTALL COMPOSER
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
$PHP_BIN composer.phar install

# GENERATE APP KEY
$PHP_BIN artisan key:generate

# BUILD STATIC SITE
$PHP_BIN please stache:warm -n -q
$PHP_BIN please ssg:generate
