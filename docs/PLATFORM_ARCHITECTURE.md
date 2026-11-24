# BlePOS — Platform Architecture

## 1. Dual-Plane Architecture

BlePOS is built on a strict two-plane architecture that separates platform management from tenant operations:

| Plane | URL Prefix | Guard | Session Cookie | Purpose |
|---|---|---|---|---|
| **Platform Control Plane** | `/platform-console` | `platform` | `PLATFORM_SESSION_COOKIE` | Platform super-admin, tenant management, billing, compliance, security |
| **Tenant Operational Data Plane** | `/` (tenant subdomain or path) | `web` | `SESSION_COOKIE` | Shop-level POS, inventory, sales, accounting, staff |

> [!IMPORTANT]
> The two planes are strictly isolated. Platform users cannot access tenant data planes and tenant users cannot access the platform console. No cross-plane impersonation routes exist — this is enforced by middleware and verified by automated tests.

---

## 2. System Hierarchy

```
Platform (Super Admin / Operations / Billing / Support / Compliance / Security)
└── Tenants (Legally distinct business organisations)
    ├── Shops / Branches (Physical locations, independent tax/receipt/eTIMS configuration)
    │   ├── Registers / Terminals (Cashier POS endpoints, cash drawers, shifts)
    │   └── Warehouses (Stock storage locations)
    └── Staff & Users (Assigned to specific shops/registers with granular RBAC)
```

### Hierarchy Rules
- A **Tenant** is the top-level isolation boundary. No tenant can see, query, or infer another tenant's data.
- A **Shop** is an operational branch of a tenant. Users assigned to Shop A cannot inspect Shop B's data without explicit multi-shop permission.
- A **Register** represents a checkout point tied to an active cash drawer and cashier shift.
- Monetary values use `decimal(15, 2)`. Floating-point arithmetic for currency is strictly prohibited.

---

## 3. Modular Monolith

BlePOS adopts a modular monolith structure:

**Presentation Layer**
- Platform Console — `/platform-console`, `platform` guard, mandatory MFA, CSP with nonces.
- Tenant Web & POS — Tenant subdomain, `web` guard, keyboard-accessible.
- API Layer — RESTful JSON, token/session auth, tenant scoping, rate limiting, idempotency headers.

**Service & Domain Layer**
- Thin controllers delegate to services: `ProvisioningEngine`, `SaleService`, `StockMovementService`, `AccountingService`, `PlatformAnalyticsService`, `PlatformGrowthService`, etc.

**Data & Persistence Layer**
- Platform models: `PlatformUser`, `PlatformRole`, `PlatformPermission`, `PlatformSession`, `PlatformSecurityEvent`, `PlatformAuditLog`, `PlatformApproval`, `Tenant`, `Plan`, `Subscription`, `Module`, `IndustryTemplate`, `ProvisioningRun`, `ProvisioningStep`.
- Tenant models: `Shop`, `Register`, `Warehouse`, `User`, `Product`, `Stock`, `Sale`, `SaleItem`, `SalePayment`, `CashierShift`, `Customer`, `Supplier`, `PurchaseOrder`, `Account`, `JournalEntry`, etc.

---

## 4. Multi-Tenancy Isolation Model

| Mechanism | Implementation |
|---|---|
| **Routing & Context Resolution** | `ResolveTenantContext` middleware resolves subdomain/path-based tenant ID |
| **Global Scoping** | `BelongsToTenant` trait on all tenant models — auto-applies `WHERE tenant_id = ?` |
| **Shop Scoping** | `BelongsToShop` trait and `EnforceShopIsolation` middleware |
| **Platform Boundary** | `RequirePlatformMfa` middleware on all `/platform-console` routes |
| **No Impersonation** | No routes exist for a platform user to act as a tenant user (Annex A 13.3) |

---

## 5. Technology Stack

| Layer | Technology |
|---|---|
| Runtime | PHP 8.3 |
| Framework | Laravel (latest) |
| Database | MySQL / SQLite (testing) |
| Queue | Laravel Queue (database driver in development; Redis recommended for production) |
| Cache | Laravel Cache (file driver in development; Redis recommended for production) |
| Auth | Laravel Auth multi-guard (platform + web) |
| MFA | TOTP (RFC 6238) via `PlatformUser.totp_secret` |
| Encryption | Laravel encryption (AES-256-CBC), bcrypt passwords, SHA-256 audit hash chains |
| Frontend | Blade templates, inline CSS (no external CDN dependencies) |
| Testing | PHPUnit, SQLite in-memory |

---

## 6. References

- `docs/ANNEX_A.md` — Full feature specification
- `docs/MULTITENANCY.md` — Detailed tenancy model
- `docs/SECURITY.md` — Security architecture
- `docs/MODULES.md` — Module catalogue
