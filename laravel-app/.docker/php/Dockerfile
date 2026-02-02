FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git unzip supervisor libzip-dev \
    && docker-php-ext-install pdo pdo_mysql zip

COPY php.ini /usr/local/etc/php/php.ini

WORKDIR /var/www/html
