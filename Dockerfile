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

RUN cat > /etc/apache2/sites-available/000-default.conf <<'EOF'
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html

    <Directory /var/www/html>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog /var/log/apache2/error.log
    CustomLog /var/log/apache2/access.log combined
</VirtualHost>
EOF

RUN cat > /usr/local/etc/php/php.ini <<'EOF'
date.timezone = Europe/Kiev
memory_limit = 512M
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 120
max_input_vars = 5000
log_errors = On
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
EOF

COPY www/ /var/www/html/

COPY --from=composer_stage /app/www/vendor /var/www/html/vendor

RUN mkdir -p /var/www/html/logs /var/www/html/upload \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/logs /var/www/html/upload
