# BlePOS

**BlePOS** is a multi-tenant, multi-shop, multi-industry Point of Sale (POS) and Business Management SaaS engineered for Kenya, East Africa, and global commercial operations.

---

## Key Features

- **Multi-Tenant Architecture**: Strict data isolation enforced via server-side session resolution, Eloquent global scopes, and separate storage boundaries.
- **Multi-Shop & Multi-Register**: Manage multiple branch locations and checkout terminals under one business entity, with strict cashier branch isolation.
- **Platform Control Plane (`/platform-console`)**: Isolated administrative environment with dedicated guard (`platform`), separate session cookie (`PLATFORM_SESSION_COOKIE`), progressive lockout, mandatory TOTP MFA, step-up authentication, and two-person approvals.
- **Append-Only Cryptographic Audit Log**: Tamper-evident SHA-256 hash chaining with SQLite trigger locks and automated verification commands.
- **Multi-Industry Engine**: Deep operational support for General Retail, Supermarkets, Pharmacies, Restaurants & Bars, Hotels & Hospitality, Wholesale, Salons, and Fuel Stations.
- **East African Payment & Compliance Rails**:
  - Safaricom M-Pesa Daraja 2.0 (STK Push, C2B Paybill/Till, and automated reconciliation).
  - KRA eTIMS (Electronic Tax Invoice Management System) fiscal compliance and QR code receipt signing.
- **Enterprise Financial Ledger**: Integrated double-entry accounting with automated general ledger entries from POS sales, inventory receipts, and expenses.

---

## System Requirements

- **PHP**: 8.3 or higher
- **Database**: SQLite (local/testing), MySQL 8.0+, MariaDB 10.11+, or PostgreSQL 15+
- **Extensions**: `bcmath`, `curl`, `mbstring`, `openssl`, `pdo`, `xml`, `zip`, `redis`
- **Node.js**: 20+ with NPM for frontend asset compilation

---

## Quick Start & Local Setup

```bash
# 1. Clone repository & install PHP dependencies
composer install

# 2. Environment configuration
cp .env.example .env
php artisan key:generate

# 3. Database migrations & seeders
php artisan migrate --seed

# 4. Create initial Platform Super Administrator via CLI
php artisan platform:create-superadmin --name="Admin User" --email="admin@blepos.co.ke"

# 5. Build frontend assets
npm install
npm run build

# 6. Start local development server
php artisan serve
```

---

## Verification & Quality Assurance

```bash
# Run complete test suite
php artisan test --compact

# Code style formatting (Laravel Pint)
vendor/bin/pint --format agent

# Validate progress and integrity ledger
./scripts/progress-check

# Verify cryptographic audit chain integrity
php artisan platform:audit-verify
```

---

## Documentation

Detailed architectural and operational specifications are available in `docs/`:

- [Architecture Specification](docs/ARCHITECTURE.md)
- [Multi-Tenancy & Isolation](docs/MULTITENANCY.md)
- [Security Architecture](docs/SECURITY.md)
- [Module Catalogue](docs/MODULES.md)
- [Platform Administration](docs/PLATFORM_ADMIN.md)
- [Dashboards & KPIs](docs/DASHBOARDS.md)
- [Industry Modules](docs/INDUSTRIES.md)
- [Integrations (M-Pesa & eTIMS)](docs/INTEGRATIONS.md)
- [Backups & Disaster Recovery](docs/BACKUPS_AND_RECOVERY.md)
- [Testing & Quality Assurance](docs/TESTING.md)
- [Production Deployment](docs/DEPLOYMENT.md)
- [Engineering Roadmap](docs/ROADMAP.md)
- [Development Progress Ledger](docs/PROGRESS.md)
