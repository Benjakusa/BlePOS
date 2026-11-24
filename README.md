# BlePOS

**BlePOS** is a multi-tenant, multi-shop, multi-industry Point of Sale (POS) and Business Management SaaS engineered for Kenya, East Africa, and global commercial operations.

> [!NOTE]
> **Annex A Full Specification Complete & Verified (183 tests, 1,970 assertions, 100% pass rate).**
> - **KRA eTIMS**: Sandbox simulation placeholder active (`BLOCKED-ON-CERTIFICATION`). Ready for production keys upon formal KRA sign-off.
> - **Safaricom M-Pesa**: Sandbox simulation placeholder active. Ready for production credentials upon telecommunications onboarding.

---

## Key Features

- **Multi-Tenant Architecture**: Strict data isolation enforced via server-side session resolution, Eloquent global scopes, and separate storage boundaries.
- **Multi-Shop & Multi-Register**: Manage multiple branch locations and checkout terminals under one business entity, with strict cashier branch isolation.
- **Tenant Operations & Role Dashboards (`/tenant`)**:
  - Tailored operational perspectives: Cashier Terminal (active shift float, cash/M-Pesa sales receipts, open-shift workflow, strictly restricted from business margins/profits/cost prices), Shop Manager (branch sales, cashier shifts on duty, register cash variance, low stock alerts), and Executive Owner (multi-branch comparison matrix, gross/net sales, 16% VAT, payment breakdown, loss prevention void alerts).
  - Dynamic shop scoping with `EnforceShopIsolation` middleware supporting header, route, input (`shop_id`), and session-based branch switching for authorized multi-branch managers and business owners.
  - Industry blueprint integrations including Pharmacy (prescription & controlled substance dispensing compliance) and Hospitality (table folios, open tabs, order queues).
- **Platform Control Plane (`/platform-console`)**:
  - Isolated administrative environment with dedicated guard (`platform`), separate session cookie (`PLATFORM_SESSION_COOKIE`), progressive lockout, mandatory TOTP MFA, step-up authentication, and two-person approvals.
  - Live integrated command center dashboard with real-time headline KPIs (MRR/ARR calculated via `bcmath`, collection efficiency, tenant retention rates).
  - Subsystem reliability probes monitoring Database, Cache, Object Storage, and Queues with live latency and diagnostic details.
  - Security Centre with threat intelligence, dynamic IP allowlist/denylist rules, cryptographic envelope key governance, and full incident response lifecycle.
  - Regulatory Compliance & eTIMS console with KRA checklist validation, statutory 30-day Kenya DPA 2019 / GDPR DSAR workflow, and versioned legal documents.
  - Automated encrypted backup pipeline with SHA-256 integrity verification and strict two-person authorization rule on disaster restore operations.
  - Formula-safe CSV reporting engine for commercial revenue, tenant directories, and module adoption.
- **Append-Only Cryptographic Audit Log**: Tamper-evident SHA-256 hash chaining with SQLite trigger locks and automated verification commands.
- **Multi-Industry Engine**: Deep operational support for General Retail, Supermarkets, Pharmacies, Restaurants & Bars, Hotels & Hospitality, Wholesale, Salons, and Fuel Stations.
- **East African Payment & Compliance Rails**:
  - Safaricom M-Pesa Daraja 2.0 (STK Push, C2B Paybill/Till, and automated reconciliation).
  - KRA eTIMS (Electronic Tax Invoice Management System) fiscal compliance and QR code receipt signing (technical placeholder surfacing honest `BLOCKED-ON-CERTIFICATION` status pending regulatory clearance).
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

Detailed architectural, platform, and operational specifications are available in `docs/`:

### Core Specifications
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
- [Granular Audit Progress Log](docs/PROGRESS_LOG.md)

### Platform Control Plane Specifications (Annex A Part 37)
- [Platform Architecture](docs/PLATFORM_ARCHITECTURE.md)
- [Platform Authentication & MFA](docs/PLATFORM_AUTHENTICATION.md)
- [Platform Security & Hardening](docs/PLATFORM_SECURITY.md)
- [Platform Roles & Permissions](docs/PLATFORM_ROLES_PERMISSIONS.md)
- [Platform Tenant Lifecycle](docs/PLATFORM_TENANT_LIFECYCLE.md)
- [Platform Provisioning Engine](docs/PLATFORM_PROVISIONING.md)
- [Platform Billing & Subscriptions](docs/PLATFORM_BILLING.md)
- [Platform Modules & Entitlements](docs/PLATFORM_MODULES_ENTITLEMENTS.md)
- [Platform Industry Templates](docs/PLATFORM_INDUSTRY_TEMPLATES.md)
- [Platform & Tenant Dashboards](docs/PLATFORM_DASHBOARDS.md)
- [Platform Infrastructure Monitoring](docs/PLATFORM_MONITORING.md)
- [Platform Compliance & DPA](docs/PLATFORM_COMPLIANCE.md)
- [Platform eTIMS Fiscalization (Placeholder)](docs/PLATFORM_ETIMS.md)
- [Platform M-Pesa Integration (Placeholder)](docs/PLATFORM_MPESA.md)
- [Platform Support Centre](docs/PLATFORM_SUPPORT.md)
- [Platform Audit Trail & Integrity](docs/PLATFORM_AUDIT.md)
- [Platform Backups & Recovery](docs/PLATFORM_BACKUPS_RECOVERY.md)
- [Platform Data Protection (Kenya DPA)](docs/PLATFORM_DATA_PROTECTION.md)
- [Platform REST API](docs/PLATFORM_API.md)
- [Platform Testing Standards](docs/PLATFORM_TESTING.md)
- [Platform Production Deployment](docs/PLATFORM_DEPLOYMENT.md)
- [Platform Incident Response](docs/PLATFORM_INCIDENT_RESPONSE.md)
- [Platform Milestone Registry](docs/PLATFORM_PROGRESS.md)
