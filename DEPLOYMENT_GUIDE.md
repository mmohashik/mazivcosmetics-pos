# 🚀 Maziv Cosmetics POS - Namecheap Hosting Guide

## 📋 Prerequisites

-   Namecheap hosting account
-   Domain: mazivcosmetics.store
-   FTP/SSH access to your hosting

## 🔧 Step-by-Step Deployment

### 1. Purchase Hosting & Setup Domain

1. **Buy hosting plan** on Namecheap (VPS recommended)
2. **Point domain** mazivcosmetics.store to hosting
3. **Setup SSL certificate** (Let's Encrypt - usually free)

### 2. Prepare Your Files

1. **Copy all files** from `c:\xampp\htdocs\` except:
    - `.env` (use `.env.production` instead)
    - `node_modules/`
    - `storage/logs/*`
    - `vendor/` (will be regenerated)

### 3. Upload to Server

**Option A: FTP Upload**

1. Connect via FTP client (FileZilla)
2. Upload all files to `public_html/` directory
3. Make sure `public/` folder contents are in document root

**Option B: Git Deployment** (Recommended)

```bash
# On server terminal
git clone https://github.com/yourusername/maziv-pos.git
cd maziv-pos
```

### 4. Server Configuration

#### A. Install Dependencies

```bash
# Install Composer dependencies
composer install --optimize-autoloader --no-dev

# Install Node.js dependencies and build assets
npm install
npm run production
```

#### B. Set Permissions

```bash
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
chown -R www-data:www-data storage/
chown -R www-data:www-data bootstrap/cache/
```

#### C. Environment Setup

1. **Rename** `.env.production` to `.env`
2. **Update database credentials** in `.env`:
    ```
    DB_HOST=localhost
    DB_DATABASE=your_cpanel_database_name
    DB_USERNAME=your_cpanel_db_user
    DB_PASSWORD=your_cpanel_db_password
    ```

### 5. Database Setup

#### A. Create Database (via cPanel)

1. Login to **cPanel**
2. Go to **MySQL Databases**
3. Create new database: `maziv_pos`
4. Create database user and assign privileges

#### B. Import Database

```bash
# Export from local
mysqldump -u root pos > pos_backup.sql

# Import to server (via cPanel phpMyAdmin or command line)
mysql -u your_db_user -p your_database_name < pos_backup.sql
```

#### C. Run Migrations (if needed)

```bash
php artisan migrate --force
php artisan db:seed --force
```

### 6. Final Configuration

#### A. Cache Configuration

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### B. Generate Application Key

```bash
php artisan key:generate
```

#### C. Storage Link

```bash
php artisan storage:link
```

### 7. Web Server Configuration

#### For Apache (.htaccess)

Create `.htaccess` in root directory:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

#### For Nginx

```nginx
server {
    listen 80;
    server_name mazivcosmetics.store www.mazivcosmetics.store;
    root /path/to/your/laravel/public;

    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## 🔒 Security Checklist

-   [ ] SSL certificate installed
-   [ ] `.env` file secured (not web accessible)
-   [ ] Database credentials secured
-   [ ] APP_DEBUG=false in production
-   [ ] Strong database passwords
-   [ ] Regular backups scheduled

## 📧 Email Configuration

Update in `.env`:

```
MAIL_HOST=mail.mazivcosmetics.store
MAIL_USERNAME=noreply@mazivcosmetics.store
MAIL_PASSWORD=your_email_password
```

## 🎯 Testing Checklist

-   [ ] Homepage loads correctly
-   [ ] Login with admin credentials works
-   [ ] Database connections working
-   [ ] File uploads working
-   [ ] Email sending working
-   [ ] SSL certificate active

## 🆘 Common Issues & Solutions

### Issue: 500 Internal Server Error

-   Check error logs: `storage/logs/laravel.log`
-   Verify file permissions
-   Check `.env` configuration

### Issue: Database Connection Failed

-   Verify database credentials
-   Check if database exists
-   Ensure database user has proper privileges

### Issue: Assets Not Loading

-   Run `npm run production`
-   Check if `public/mix-manifest.json` exists
-   Verify asset paths in config

## 📞 Support

-   Namecheap Support: https://www.namecheap.com/support/
-   Laravel Documentation: https://laravel.com/docs

---

**Domain:** mazivcosmetics.store
**Application:** Maziv Cosmetics POS System
**Framework:** Laravel 10 + React
