FROM composer:2 AS composer_stage

WORKDIR /app

COPY www/composer.json /app/www/composer.json

RUN cd /app/www \
    && composer install --no-interaction --no-dev --optimize-autoloader


FROM php:8.2-apache

WORKDIR /var/www/html

RUN a2enmod rewrite \
    && docker-php-ext-install mysqli \
    && docker-php-ext-enable mysqli

COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php/php.ini /usr/local/etc/php/php.ini

COPY www/ /var/www/html/

COPY --from=composer_stage /app/www/vendor /var/www/html/vendor

RUN chown -R www-data:www-data /var/www/html \
    && mkdir -p /var/www/html/logs /var/www/html/upload \
    && chown -R www-data:www-data /var/www/html/logs /var/www/html/upload

