FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev libcurl4-openssl-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql mbstring curl \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
RUN mkdir -p /var/www/html/storage \
    && chown www-data:www-data /var/www/html/storage

WORKDIR /var/www/html
