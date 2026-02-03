FROM php:8.4-fpm

ARG WWWUSER=1001
ARG WWWGROUP=1001

RUN apt-get update && apt-get install -y \
    git unzip supervisor libzip-dev \
    && docker-php-ext-install pdo pdo_mysql zip \
    && usermod -u ${WWWUSER} www-data \
    && groupmod -g ${WWWGROUP} www-data

RUN pecl install redis \
    && docker-php-ext-enable redis
    
RUN mkdir -p /var/log/supervisor \
    && chown -R www-data:www-data /var/log/supervisor

COPY php.ini /usr/local/etc/php/php.ini

WORKDIR /var/www/html

USER www-data