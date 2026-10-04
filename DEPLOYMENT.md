<div align="center">

# 🚀 Deployment Guide

**Complete guide for deploying KASHA to production**

</div>

---

## 📋 Table of Contents

- [Deployment Options](#deployment-options)
- [Option 1: Vercel (Recommended for v1)](#option-1-vercel)
- [Option 2: Laravel Cloud (Best for Production)](#option-2-laravel-cloud)
- [Option 3: Railway (Easy Alternative)](#option-3-railway)
- [Option 4: VPS (Full Control)](#option-4-vps)
- [Environment Variables](#environment-variables)
- [Post-Deployment Checklist](#post-deployment-checklist)
- [Troubleshooting](#troubleshooting)

---

## 🎯 Deployment Options

| Platform | Difficulty | Cost | Recommended For |
|----------|-----------|------|-----------------|
| **Vercel** | Easy | Free tier | v1, rapid prototyping |
| **Laravel Cloud** | Easy | Paid ($10+/mo) | Production, stability |
| **Railway** | Easy | Free tier then paid | Small production apps |
| **VPS** | Hard | $5+/mo | Full control, custom setup |

---

## Option 1: Vercel

### Prerequisites

- Vercel account (free)
- GitHub repository pushed
- Supabase project ready

### Step 1: Prepare Local Build

```bash
# Build assets for production
npm run build

# Commit build artifacts
git add public/build
git commit -m "build: production assets"
git push origin main
```

### Step 2: Setup Environment Variables

Go to Vercel Dashboard → Project Settings → Environment Variables:

```bash
# Application
APP_NAME=KASHA
APP_ENV=production
APP_KEY=base64:your-app-key-from-.env
APP_DEBUG=false
APP_URL=https://your-project.vercel.app

# Database (Supabase)
DB_CONNECTION=pgsql
DB_HOST=your-project.supababase.co
DB_PORT=6543
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=your-supabase-password

# Supabase
SUPABASE_URL=https://your-project.supabase.co
SUPABASE_KEY=your-supabase-anon-key

# Laravel Configuration
SESSION_DRIVER=cookie
CACHE_DRIVER=database
QUEUE_CONNECTION=database
```

### Step 3: Deploy to Vercel

1. Go to [vercel.com](https://vercel.com)
2. Click "Add New Project"
3. Import from GitHub → Select `FerrDxD/kasha`
4. Vercel will auto-detect configuration
5. Click "Deploy"

### Step 4: Setup Cron Jobs

Vercel → Settings → Cron Jobs:

```bash
# Recurring transactions (daily at midnight)
0 0 * * * https://your-project.vercel.app/internal/cron/recurring

# Monthly reports (1st of each month)
0 0 1 * * https://your-project.vercel.app/internal/cron/reports
```

### Step 5: Run Migrations

Since Vercel is serverless, run migrations via:

```bash
# Option A: SSH into Vercel (if available)
# Option B: Use Supabase direct connection for migrations
# Option C: Create a one-time deployment command
```

### Known Limitations

- No persistent filesystem
- No queue workers
- Cold starts
- Function execution time limits

---

## Option 2: Laravel Cloud

### Prerequisites

- Laravel Cloud account
- GitHub repository

### Step 1: Install Laravel Cloud CLI

```bash
npm install -g @laravel/cloud
```

### Step 2: Login

```bash
cloud login
```

### Step 3: Deploy

```bash
cd /path/to/kasha
cloud deploy
```

Laravel Cloud will:
- Auto-detect Laravel application
- Setup PostgreSQL database
- Setup storage
- Deploy to edge network
- Configure SSL automatically
- Setup environment variables

### Step 4: Run Migrations

```bash
cloud shell
php artisan migrate --force
```

### Benefits

- Official Laravel hosting
- Optimized for Laravel
- Automatic scaling
- Built-in caching
- Edge network deployment
- 99.9% uptime SLA

---

## Option 3: Railway

### Prerequisites

- Railway account
- GitHub repository

### Step 1: Create Project

1. Go to [railway.app](https://railway.app)
2. New Project → Deploy from GitHub
3. Select `FerrDxD/kasha`

### Step 2: Setup Database

Railway will create a PostgreSQL database automatically, or you can connect to Supabase:

```bash
# In Railway dashboard, add environment variable:
DB_CONNECTION=pgsql
DB_HOST=${{RAILWAY_PRIVATE_DOMAIN}}
DB_PORT=5432
DB_DATABASE=${{PGDATABASE}}
DB_USERNAME=${{PGUSER}}
DB_PASSWORD=${{PGPASSWORD}}
```

### Step 3: Setup Environment Variables

Add all required environment variables in Railway dashboard.

### Step 4: Configure Build

In `railway.json`:

```json
{
  "build": {
    "builder": "NIXPACKS"
  },
  "deploy": {
    "startCommand": "php artisan serve --host=0.0.0.0 --port=${PORT}"
  }
}
```

### Step 5: Run Migrations

Add deployment command in Railway dashboard:

```bash
php artisan migrate --force
```

### Benefits

- Easy setup
- Free tier available
- Automatic HTTPS
- Built-in monitoring

---

## Option 4: VPS (DigitalOcean, Linode, etc.)

### Prerequisites

- VPS with Ubuntu 22.04
- Domain name (optional)
- SSH access

### Step 1: Update System

```bash
apt update && apt upgrade -y
```

### Step 2: Install Dependencies

```bash
# Install PHP 8.2 and extensions
apt install php8.2-fpm php8.2-pgsql php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-bcmath -y

# Install Nginx
apt install nginx -y

# Install Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Install Node.js and npm
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install nodejs -y
```

### Step 3: Clone Repository

```bash
cd /var/www
git clone https://github.com/FerrDxD/kasha.git
cd kasha
```

### Step 4: Install Dependencies

```bash
# PHP dependencies
composer install --optimize-autoloader --no-dev

# Node dependencies
npm install

# Build assets
npm run build
```

### Step 5: Setup Environment

```bash
cp .env.example .env
nano .env
```

Fill in production environment variables.

### Step 6: Set Permissions

```bash
chown -R www-data:www-data /var/www/kasha
chmod -R 755 /var/www/kasha/storage
chmod -R 755 /var/www/kasha/bootstrap/cache
```

### Step 7: Configure Nginx

```bash
nano /etc/nginx/sites-available/kasha
```

Add configuration:

```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    root /var/www/kasha/public;

    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

Enable site:

```bash
ln -s /etc/nginx/sites-available/kasha /etc/nginx/sites-enabled/
nginx -t
systemctl restart nginx
```

### Step 8: Run Migrations

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
```

### Step 9: Setup SSL with Certbot

```bash
apt install certbot python3-certbot-nginx -y
certbot --nginx -d your-domain.com -d www.your-domain.com
```

### Step 10: Setup Supervisor (for Queue Workers)

```bash
apt install supervisor -y
nano /etc/supervisor/conf.d/kasha-worker.conf
```

Add:

```ini
[program:kasha-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/kasha/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/kasha/storage/logs/worker.log
```

Restart supervisor:

```bash
supervisorctl reread
supervisorctl update
supervisorctl start kasha-worker:*
```

### Step 11: Setup Cron Jobs

```bash
crontab -e
```

Add:

```bash
* * * * * cd /var/www/kasha && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🔐 Environment Variables

### Required Variables

```bash
# Application
APP_NAME=KASHA
APP_ENV=production
APP_KEY=base64:your-app-key
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=pgsql
DB_HOST=your-db-host
DB_PORT=5432
DB_DATABASE=kasha
DB_USERNAME=kasha_user
DB_PASSWORD=your-db-password

# Supabase (if using)
SUPABASE_URL=https://your-project.supabase.co
SUPABASE_KEY=your-supabase-anon-key

# Laravel
SESSION_DRIVER=database
CACHE_DRIVER=database
QUEUE_CONNECTION=database
```

### Optional Variables

```bash
# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password

# Storage
FILESYSTEM_DISK=local
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket
```

---

## ✅ Post-Deployment Checklist

- [ ] Application loads in browser
- [ ] SSL certificate valid
- [ ] Database migrations run successfully
- [ ] Environment variables configured
- [ ] File storage working
- [ ] Queue workers running (if applicable)
- [ ] Cron jobs configured
- [ ] Email sending works
- [ ] Error logging enabled
- [ ] Monitoring setup (Sentry, etc.)
- [ ] Backup schedule configured
- [ ] Security headers configured
- [ ] Performance optimized (caching, CDN)

---

## 🔧 Troubleshooting

### 500 Internal Server Error

```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check Nginx logs
tail -f /var/log/nginx/error.log

# Check PHP-FPM logs
tail -f /var/log/php8.2-fpm.log
```

### Database Connection Failed

```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

### Permission Issues

```bash
# Fix storage permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 755 storage bootstrap/cache
```

### Queue Not Processing

```bash
# Check supervisor status
supervisorctl status

# Restart worker
supervisorctl restart kasha-worker:*
```

### Assets Not Loading

```bash
# Rebuild assets
npm run build

# Clear cache
php artisan view:clear
php artisan cache:clear
```

---

## 📚 Additional Resources

- [Laravel Deployment Documentation](https://laravel.com/docs/deployment)
- [Vercel PHP Runtime](https://github.com/vercel-community/php)
- [Laravel Cloud Documentation](https://laravel.com/docs/cloud)
- [Railway Documentation](https://docs.railway.app)
- [Nginx Laravel Guide](https://www.digitalocean.com/community/tutorials/how-to-deploy-a-laravel-application-with-nginx-on-ubuntu-20-04)

---

<div align="center">

**Need help? Open an issue on GitHub**

[⬆ Back to Top](#-deployment-guide)

</div>
