FROM php:8.1-apache

# Install system dependencies and PHP extensions in one layer
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    && docker-php-ext-install pdo_mysql pdo_sqlite mbstring exif pcntl gd zip \
    && a2enmod rewrite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application code first
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Ensure public directory exists
RUN mkdir -p /var/www/html/public

# Configure Apache
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Generate Laravel optimizations
RUN php artisan config:cache || true

# Create .env file if it doesn't exist
RUN if [ ! -f .env ]; then cp .env.example .env; fi

# Generate application key
RUN php artisan key:generate --force || true

# Create database directory for SQLite fallback
RUN mkdir -p /var/www/html/database

# Set proper permissions for database
RUN chown -R www-data:www-data /var/www/html/database \
    && chmod -R 775 /var/www/html/database

# Expose port 80
EXPOSE 80

# Create a startup script
RUN echo '#!/bin/bash\n\
# Wait for database to be ready\n\
sleep 5\n\
# Run migrations and seeders\n\
php artisan migrate --force || true\n\
php artisan db:seed --force || true\n\
# Start Apache\n\
exec apache2-foreground' > /start.sh && chmod +x /start.sh

# Start with our custom script
CMD ["/start.sh"]
