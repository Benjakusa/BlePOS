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

M-Pesa production configuration also requires `MPESA_ENCRYPTION_KEY` to decode to exactly 32 bytes, `MPESA_CALLBACK_SECRET` to contain at least 32 characters, and `MPESA_CALLBACK_ALLOWED_IPS` to contain callback source IPs/CIDRs supplied for your Daraja account. Set the callback secret before applying the callback-token hashing migration; production boot fails when either callback control is missing. Callback source checks use Laravel's resolved client IP, so configure trusted proxies correctly. Callback bearer tokens are derived from the callback secret and credential UUID; only their SHA-256 hashes are stored. Keep this secret stable unless performing a coordinated token-hash rotation. Do not publish callback URLs. Keep tenant platform-fallback collection disabled; production boot rejects it until tenant remittance accounting is implemented.

For encryption key rotation, keep the current key available for unversioned legacy ciphertext, configure a versioned JSON keyring in `MPESA_ENCRYPTION_KEYS` (for example `{"v1":"base64:...old...","v2":"base64:...new..."}`), and set `MPESA_ENCRYPTION_ACTIVE_KEY_VERSION=v2`. Run `php artisan mpesa:reencrypt-credentials`; keep prior keys configured until the command finishes and backups using the old ciphertext have expired. Unversioned legacy ciphertext uses `MPESA_ENCRYPTION_KEY`.

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

The scheduler runs pending M-Pesa timeout handling every minute, reports paid POS attempts without completed sales every five minutes, the encrypted platform database backup daily at 01:00, and expires backup artifacts at 02:00 using `PLATFORM_BACKUP_RETENTION_DAYS` (30 days by default). Keep the scheduler running so cashiers receive timeout notifications and can retry unconfirmed POS checkouts. Pending STK checkouts reserve tracked inventory until timeout or settlement; a late successful callback reacquires stock and completes if available. If stock is unavailable or a second attempt is paid, the transaction is marked `refund_required`, the cashier and platform billing staff are notified, and external refund processing must be handled and recorded by staff. Monitor scheduled commands and verify restore drills on the selected production database engine; only SQLite sandbox restores are currently executable in-app.

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
