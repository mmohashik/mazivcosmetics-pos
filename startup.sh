#!/bin/bash

# Laravel startup script for Azure App Service
echo "Starting Laravel application..."

# Navigate to application directory
cd /home/site/wwwroot

# Set permissions
chmod -R 755 storage bootstrap/cache

# Create necessary directories if they don't exist
mkdir -p storage/logs
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p bootstrap/cache

# Set proper permissions for Laravel
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Install dependencies if vendor doesn't exist
if [ ! -d "vendor" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-dev --optimize-autoloader
fi

# Generate app key if not set
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Clear and cache configurations
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Run database migrations
php artisan migrate --force

# Cache configurations for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start PHP built-in server
echo "Starting PHP server..."
php artisan serve --host=0.0.0.0 --port=8000
