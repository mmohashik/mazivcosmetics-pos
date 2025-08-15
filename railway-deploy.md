# 🚀 Deploy Laravel POS to Railway (FREE)

## Step 1: Prepare Your Application

### 1.1 Create Production Environment File

Create `.env.production` with these settings:

```env
APP_NAME="Maziv Cosmetics POS"
APP_ENV=production
APP_KEY=base64:Rf51THet+CticDtUmOq9XJBaa9lDO9Y50bhx7Ds0NSI=
APP_DEBUG=false
APP_URL=https://mazivcosmetics.store

LOG_CHANNEL=stack
LOG_LEVEL=error

# Railway will provide these automatically
DB_CONNECTION=mysql
DB_HOST=${MYSQLHOST}
DB_PORT=${MYSQLPORT}
DB_DATABASE=${MYSQLDATABASE}
DB_USERNAME=${MYSQLUSER}
DB_PASSWORD=${MYSQLPASSWORD}

CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Maziv Cosmetics"
```

### 1.2 Create Dockerfile

```dockerfile
FROM php:8.1-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nginx

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u 1000 -d /home/user user
RUN mkdir -p /home/user/.composer && \
    chown -R user:user /home/user

# Set working directory
WORKDIR /var/www

# Copy existing application directory contents
COPY . /var/www

# Copy existing application directory permissions
COPY --chown=user:user . /var/www

# Change current user to user
USER user

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Change back to root
USER root

# Create storage and cache directories
RUN mkdir -p /var/www/storage/logs
RUN mkdir -p /var/www/bootstrap/cache

# Set permissions
RUN chown -R www-data:www-data /var/www
RUN chmod -R 755 /var/www/storage
RUN chmod -R 755 /var/www/bootstrap/cache

# Configure nginx
COPY nginx.conf /etc/nginx/nginx.conf

# Expose port 9000 and start php-fpm server
EXPOSE 8080
CMD ["sh", "-c", "php artisan config:cache && php artisan migrate --force && nginx -g 'daemon off;' & php-fpm"]
```

### 1.3 Create nginx.conf

```nginx
events {
    worker_connections 1024;
}

http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    server {
        listen 8080;
        server_name _;
        root /var/www/public;
        index index.php index.html;

        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }

        location ~ \.php$ {
            fastcgi_pass 127.0.0.1:9000;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
            include fastcgi_params;
        }

        location ~ /\.ht {
            deny all;
        }
    }
}
```

## Step 2: Deploy to Railway

### 2.1 Sign Up for Railway

1. Go to https://railway.app
2. Sign up with GitHub account (free)
3. Connect your GitHub account

### 2.2 Push Code to GitHub

```bash
# Initialize git repository
git init
git add .
git commit -m "Initial commit - Laravel POS"

# Create GitHub repository and push
# (Create repo on GitHub first: mazivcosmetics-pos)
git remote add origin https://github.com/yourusername/mazivcosmetics-pos.git
git branch -M main
git push -u origin main
```

### 2.3 Deploy on Railway

1. **New Project** → **Deploy from GitHub repo**
2. **Select** your `mazivcosmetics-pos` repository
3. **Add MySQL database**:
    - Click "New" → "Database" → "Add MySQL"
    - Railway will auto-generate connection variables

### 2.4 Configure Environment Variables

In Railway dashboard, add these variables:

-   `APP_NAME`: Maziv Cosmetics POS
-   `APP_ENV`: production
-   `APP_DEBUG`: false
-   `APP_URL`: https://your-app.railway.app (Railway provides this)

### 2.5 Custom Domain Setup

1. In Railway dashboard → **Settings** → **Domains**
2. **Add Domain**: `mazivcosmetics.store`
3. **Update DNS** in Namecheap:
    - Add CNAME record: `www` → `your-app.railway.app`
    - Add A record: `@` → Railway IP (they'll provide)

## Step 3: Database Migration

Railway will automatically run migrations on deploy, but you can also run:

```bash
# In Railway console
php artisan migrate:fresh --seed --force
```

## 🎉 Your Laravel POS will be live at:

-   https://mazivcosmetics.store
-   Free for small usage!

## 💡 Alternative: Use Railway's Template

1. Go to https://railway.app/template/laravel
2. Click "Deploy Now"
3. Connect your GitHub repo
4. Railway handles everything automatically!
