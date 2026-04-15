FROM php:8.2-apache

RUN a2enmod rewrite

RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80