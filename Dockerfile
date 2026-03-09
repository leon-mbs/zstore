FROM composer:2 AS composer_stage

WORKDIR /app

COPY www/composer.json /app/www/composer.json

RUN cd /app/www \
    && composer install --no-interaction --no-dev --optimize-autoloader --ignore-platform-reqs


FROM php:8.2-apache

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y libzip-dev libgmp-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install mysqli zip gmp gd \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php/php.ini /usr/local/etc/php/php.ini

COPY www/ /var/www/html/

COPY --from=composer_stage /app/www/vendor /var/www/html/vendor

RUN mkdir -p /var/www/html/logs /var/www/html/upload \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/logs /var/www/html/upload

