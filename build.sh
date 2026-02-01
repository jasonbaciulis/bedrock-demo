#!/bin/sh
set -e

echo "=== Installing build dependencies ==="
dnf install -y wget tar xz gcc make autoconf \
    libxml2-devel openssl-devel sqlite-devel \
    libcurl-devel oniguruma-devel libpng-devel \
    libzip-devel libicu-devel

# Download and compile PHP 8.4
PHP_VERSION="8.4.4"
echo "=== Downloading PHP ${PHP_VERSION} source ==="
wget -O php.tar.xz "https://www.php.net/distributions/php-${PHP_VERSION}.tar.xz"
tar -xf php.tar.xz
cd "php-${PHP_VERSION}"

echo "=== Configuring PHP ==="
./configure \
    --prefix=/usr/local \
    --disable-all \
    --enable-cli \
    --enable-phar \
    --enable-filter \
    --enable-tokenizer \
    --enable-ctype \
    --enable-mbstring \
    --enable-intl \
    --enable-bcmath \
    --enable-gd \
    --with-zlib \
    --with-openssl \
    --with-curl \
    --with-zip \
    --enable-dom \
    --enable-xml \
    --enable-xmlreader \
    --enable-xmlwriter \
    --enable-simplexml \
    --with-libxml \
    --enable-pdo \
    --with-pdo-sqlite \
    --enable-fileinfo \
    --enable-session \
    --enable-posix \
    --enable-pcntl \
    2>&1

echo "=== Compiling PHP (this will take a while) ==="
make -j$(nproc) 2>&1
make install 2>&1

cd ..
PHP_BIN="/usr/local/bin/php"

# Verify PHP version
echo "=== PHP version ==="
$PHP_BIN -v

# INSTALL COMPOSER
echo "=== Installing Composer ==="
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
echo "=== Installing Composer dependencies ==="
$PHP_BIN composer.phar install --no-interaction --no-dev --optimize-autoloader

# GENERATE APP KEY
echo "=== Generating app key ==="
$PHP_BIN artisan key:generate

# BUILD STATIC SITE
echo "=== Warming stache ==="
$PHP_BIN please stache:warm -n -q
echo "=== Generating static site ==="
$PHP_BIN please ssg:generate

echo "=== Build complete ==="
