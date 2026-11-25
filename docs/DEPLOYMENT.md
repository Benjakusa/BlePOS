# BlePOS — Production Deployment Specification

Run `php artisan platform:production-check` against the deployment environment before release. Configure a production `APP_ENV`, disable debug, use secure session cookies and HTTPS, configure trusted proxy addresses, use a managed non-SQLite database, durable object storage for uploads and backups, and persistent queue/cache services. Backup workers require `mysqldump` or `pg_dump` for the corresponding database engine.

## 1. Environment & Infrastructure Requirements

- **Operating System**: Linux (Ubuntu 22.04 LTS / 24.04 LTS or Debian 12).
- **Runtime**: PHP 8.3+ with extensions: `pdo_mysql` / `pdo_pgsql`, `pdo_sqlite`, `bcmath`, `curl`, `mbstring`, `openssl`, `xml`, `zip`, `redis`.
- **Web Server**: Nginx or Caddy with TLS 1.3 termination.
- **Relational Database**: MySQL 8.0+ / MariaDB 10.11+ / PostgreSQL 15+.
- **Cache & Queue**: Redis 7.0+ for high-throughput session, cache, and queue management.
- **Storage**: AWS S3 compatible object storage for receipt archives, product images, and backups.

---

## 2. Production Environment Variables

Key production configuration flags:

```dotenv
APP_NAME=BlePOS
APP_ENV=production
APP_DEBUG=false
APP_URL=https://blepos.co.ke
APP_KEY=base64:replace-with-managed-secret

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=blepos_central
DB_USERNAME=blepos_user
DB_PASSWORD=secret_production_password

# Platform Security & Isolation
PLATFORM_FORCE_HTTPS=true
SESSION_SECURE_COOKIE=true
PLATFORM_SESSION_SECURE_COOKIE=true
PLATFORM_SESSION_COOKIE=blepos_platform_session
SESSION_COOKIE=blepos_session
TRUSTED_PROXIES=127.0.0.1,10.0.0.0/8
FILESYSTEM_DISK=s3
BACKUP_DISK=s3
QUEUE_CONNECTION=redis
CACHE_STORE=redis
PLATFORM_IP_ALLOWLIST_ENABLED=false
PLATFORM_IP_ALLOWLIST=

# M-Pesa Daraja Configuration
MPESA_ENV=production
MPESA_CONSUMER_KEY=
MPESA_CONSUMER_SECRET=
MPESA_PASSKEY=

# KRA eTIMS Configuration
ETIMS_ENV=production
ETIMS_SERVER_URL=https://etims.kra.go.ke
```

The application refuses to boot in production when debug is enabled, `APP_KEY` is missing, secure session cookies are disabled, SQLite is selected, or upload/backup storage defaults to local disks. Store secrets in the host's secret manager. Configure the `s3` disk credentials, private bucket policies, lifecycle retention, encryption at rest, Redis, trusted proxy addresses, and TLS termination before deployment. `.env.example` uses non-debug defaults; its local database and storage values are for development only.

---

## 3. Deployment Procedure

```bash
# 1. Enter maintenance mode
php artisan down --render="errors::503" --secret="deploy-bypass-token"

# 2. Pull latest release & install dependencies
git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction

# 3. Compile frontend production assets
npm ci
npm run build

# 4. Run database migrations
php artisan migrate --force

# 5. Optimize configuration and route caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Restart queue workers & exit maintenance mode
php artisan queue:restart
php artisan up
```

The Vite development server is used only in the `local` environment and only while its `/@vite/client` endpoint responds. Staging and production always serve versioned assets from `public/build`, even if a stale `public/hot` file remains or the PHP container can reach a Vite server that a browser cannot. Production boot checks that the manifest and both compiled entrypoints exist; run `npm ci && npm run build` as part of every release before restarting the PHP service. `npm run dev` and `npm run build` also clear an old hot file before starting.

---

## 4. Background Processes & Scheduler

Crontab configuration for scheduled tasks:
```cron
* * * * * cd /var/www/blepos && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler runs the encrypted platform database backup daily at 01:00 and expires backup artifacts at 02:00 using `PLATFORM_BACKUP_RETENTION_DAYS` (30 days by default). Monitor both scheduled commands and verify restore drills on the selected production database engine; only SQLite sandbox restores are currently executable in-app.

Supervisor configuration (`/etc/supervisor/conf.d/blepos-worker.conf`):
```ini
[program:blepos-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/blepos/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/blepos/storage/logs/worker.log
```
