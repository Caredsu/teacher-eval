FROM php:8.2-apache

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install MongoDB extension
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# Copy project files
COPY . /var/www/html

# Set proper permissions (optional)
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80FROM php:8.2-cli

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev && \
    docker-php-ext-install zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . /app

RUN composer install

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
