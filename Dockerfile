FROM php:8.4-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    libssl-dev \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Install MongoDB extension
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Disable default Apache config
RUN a2dissite 000-default || true

# Copy custom Apache configuration
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf

# Configure Apache to listen on port 8080 (only in ports.conf)
RUN sed -i 's/80/8080/g' /etc/apache2/ports.conf

# Enable our custom site
RUN a2ensite 000-default

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Expose port
EXPOSE 8080

# Start Apache in foreground
CMD ["apache2-foreground"]
