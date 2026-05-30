# MXSchedule — Production Deployment Guide

This document is the authoritative checklist for deploying MXSchedule to a production server.
Follow every step in order. Steps marked **🔴 Critical** must not be skipped.

---

## Prerequisites

| Requirement | Minimum Version |
|-------------|----------------|
| PHP | 8.3+ |
| Composer | 2.x |
| Node.js | 20+ (for asset compilation) |
| MySQL / PostgreSQL | 8.0+ / 14+ |
| Redis | 7+ *(optional, recommended for high-volume queues)* |
| OpenSSL PHP extension | enabled |
| GD / Imagick PHP extension | enabled *(for QR code rendering)* |
| Queue worker supervisor | systemd / Supervisor |

---

## Step 1 — Clone and Install Dependencies

```bash
git clone <your-repo-url> /var/www/mxschedule
cd /var/www/mxschedule

composer install --optimize-autoloader --no-dev
npm install
npm run build
```

---

## Step 2 — Environment Configuration 🔴 Critical

```bash
cp .env.example .env
```

Open `.env` and configure **every** value. At minimum:

| Variable | Production Value |
|----------|----------------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | Your actual domain (e.g. `https://mxschedule.university.edu`) |
| `APP_KEY` | Generated in Step 3 |
| `ALLOWED_EMAIL_DOMAIN` | Your institutional email domain (e.g. `@university.edu`) |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Your DB credentials |
| `MAIL_MAILER` | `smtp` — **students won't receive notifications if this is `log`** |
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` | Your SMTP provider |
| `MAIL_FROM_ADDRESS` | A real, verified sender address |
| `LOG_LEVEL` | `warning` — do not use `debug` (leaks PII) |
| `SESSION_ENCRYPT` | `true` |
| `QUEUE_CONNECTION` | `database` (or `redis` for high load) |

---

## Step 3 — Generate Application Key 🔴 Critical

```bash
php artisan key:generate
```

---

## Step 4 — Run Database Migrations 🔴 Critical

```bash
php artisan migrate --force
```

---

## Step 5 — Seed Roles, Permissions, and Settings 🔴 Critical

Run only the non-demo seeders in production:

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan db:seed --class=SettingsSeeder
```

> ⚠️ **Do NOT run `DatabaseSeeder` or `DemoDataSeeder` in production.** They create fake data and provision the admin account with the password `password`.

---

## Step 6 — Create the Super Admin Account 🔴 Critical

```bash
php artisan tinker
```

```php
$admin = App\Models\User::create([
    'name'               => 'System Administrator',
    'email'              => 'admin@your-domain.com',
    'password'           => Hash::make('CHANGE_ME_TO_A_STRONG_PASSWORD'),
    'email_verified_at'  => now(),
    'is_active'          => true,
]);
$admin->assignRole('super_admin');
```

**Change the password immediately after first login.**

---

## Step 7 — Generate RSA Key Pair 🔴 Critical

QR exam passes are signed with RSA-2048. Without this step the system falls back to
a less-secure HMAC key stored in the database.

```bash
php artisan exam:generate-keys
```

This creates:
- `storage/app/keys/exam_private.pem` (permissions: `0600`)
- `storage/app/keys/exam_public.pem` (permissions: `0644`)

> ⚠️ **Never commit `storage/app/keys/` to version control.**
> Ensure these paths are in `.gitignore`.
> Back up the private key securely — if lost, all previously issued QR passes become unverifiable.

---

## Step 8 — Storage Link

```bash
php artisan storage:link
```

This creates `public/storage` → `storage/app/public` symlink, required for PDF exam passes.

---

## Step 9 — Optimize for Production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## Step 10 — File Permissions

```bash
chown -R www-data:www-data /var/www/mxschedule
chmod -R 755 /var/www/mxschedule/storage
chmod -R 755 /var/www/mxschedule/bootstrap/cache
chmod 600 /var/www/mxschedule/storage/app/keys/exam_private.pem
```

---

## Step 11 — Queue Workers 🔴 Critical

The scheduling engine, PDF generation, and email notifications all run in the background
via Laravel queues. Without workers, these operations will never execute.

### Recommended: Supervisor Configuration

Create `/etc/supervisor/conf.d/mxschedule-worker.conf`:

```ini
[program:mxschedule-scheduling]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/mxschedule/artisan queue:work --queue=scheduling --tries=1 --timeout=600 --sleep=3
directory=/var/www/mxschedule
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
stdout_logfile=/var/log/mxschedule/scheduling.log
stderr_logfile=/var/log/mxschedule/scheduling_error.log

[program:mxschedule-notifications]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/mxschedule/artisan queue:work --queue=notifications --tries=3 --timeout=300 --sleep=3
directory=/var/www/mxschedule
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
stdout_logfile=/var/log/mxschedule/notifications.log
stderr_logfile=/var/log/mxschedule/notifications_error.log

[program:mxschedule-default]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/mxschedule/artisan queue:work --queue=default --tries=3 --timeout=120 --sleep=3
directory=/var/www/mxschedule
autostart=true
autorestart=true
user=www-data
numprocs=2
stdout_logfile=/var/log/mxschedule/default.log
stderr_logfile=/var/log/mxschedule/default_error.log
```

```bash
mkdir -p /var/log/mxschedule
supervisorctl reread
supervisorctl update
supervisorctl start all
```

### Scheduler

Run Laravel's scheduler every minute so completed sessions are closed and absent
students are marked as no-shows:

```cron
* * * * * cd /var/www/mxschedule && php artisan schedule:run >> /dev/null 2>&1
```

---

## Step 12 — Web Server Configuration (Nginx)

```nginx
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;

    root /var/www/mxschedule/public;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header Referrer-Policy "strict-origin-when-cross-origin";
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Block direct access to key storage
    location ~ /storage/app/keys {
        deny all;
    }
}
```

---

## Step 13 — Post-Deployment Verification Checklist

- [ ] Visit `https://your-domain.com` — welcome page loads
- [ ] Visit `https://your-domain.com/up` — returns HTTP 200 (Laravel health check)
- [ ] Log in as super admin — dashboard loads
- [ ] Create a test exam and trigger scheduling — verify queue worker processes it
- [ ] Check that PDF exam passes are generated and downloadable
- [ ] Check that email notifications arrive in student inboxes
- [ ] Scan a test QR pass at `invigilator/scanner` — verify RS256 validation works
- [ ] Check `php artisan queue:failed` — no failed jobs
- [ ] Review `storage/logs/laravel.log` — no unexpected errors

---

## Updating / Re-Deploying

```bash
git pull
composer install --optimize-autoloader --no-dev
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
supervisorctl restart all
```

---

## Security Notes

- **Never share `storage/app/keys/exam_private.pem`** — keep it server-only.
- **Rotate the RSA key pair** if the server is compromised. After rotation, re-run `php artisan exam:generate-keys --force` and reschedule all affected exams to regenerate passes.
- **Rotate `APP_KEY`** with `php artisan key:rotate` if it is ever leaked — this invalidates all sessions.
- Ensure `storage/app/keys/` is not served by the web server (the Nginx config above blocks it).
