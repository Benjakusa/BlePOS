# BlePOS — Platform Deployment & Production Runbook

## 1. Production Requirements

- **PHP**: 8.3 with extensions `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `json`, `mbstring`, `openssl`, `pcre`, `pdo_mysql`, `tokenizer`, `xml`.
- **Database**: MySQL 8.0+ / MariaDB 10.6+ with strict mode enabled.
- **Cache & Queues**: Redis 6.2+ recommended for production session management, caching, and background queues.
- **Web Server**: Nginx with HTTP/2 and modern TLS 1.3 ciphers.

## 2. Deployment Pipeline

1. **Pull Latest Release**: Git checkout of tagged release.
2. **Install Dependencies**:
   ```bash
   composer install --no-dev --optimize-autoloader --no-interaction
   npm ci && npm run build
   ```
3. **Database Migrations**:
   ```bash
   php artisan migrate --force --no-interaction
   ```
4. **Configuration & Route Caching**:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
5. **Queue Worker Restart**:
   ```bash
   php artisan queue:restart
   ```
6. **Audit & Health Verification**:
   ```bash
   php artisan platform:audit-verify
   ```

## 3. Environment Variables Checklist

Ensure these production variables are configured in `.env`:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `PLATFORM_SESSION_COOKIE=blepos_platform_session`
- `SESSION_SECURE_COOKIE=true`
- `SESSION_HTTP_ONLY=true`
- `SESSION_SAME_SITE=strict`
