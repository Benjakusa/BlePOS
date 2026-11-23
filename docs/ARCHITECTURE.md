# BlePOS — Architecture Specification

## 1. Executive Summary & Product Scope

**BlePOS** is a multi-tenant, multi-shop, multi-industry Point of Sale (POS) and Business Management SaaS engineered for Kenya, East Africa, and global commercial markets.

The platform is designed around strict separation between:
1. **Central Platform Control Plane**: Platform administration, tenant onboarding, automated provisioning, subscription management, platform billing, module catalogue, industry templates, compliance oversight, infrastructure health monitoring, and system-wide security operations.
2. **Tenant Operational Data Plane**: Shop-level POS terminals, barcode processing, inventory control, purchase orders, supplier management, multi-currency/M-Pesa payments, KRA eTIMS fiscalization, double-entry accounting ledgers, and industry-specific workflows.

---

## 2. System Hierarchy

```
Platform (Super Admin / Operations / Billing / Support / Compliance / Security)
└── Tenants (Legally distinct business organizations)
    ├── Shops / Branches (Physical locations, independent tax/receipt/M-Pesa/eTIMS configuration)
    │   ├── Registers / Terminals (Cashier POS endpoints, cash drawers, shifts)
    │   └── Warehouses (Stock storage locations)
    └── Staff & Users (Assigned to specific shops and registers with granular RBAC)
```

### Hierarchy Rules:
- A Tenant is the top-level isolation boundary. No tenant can see, query, or infer another tenant's data.
- A Shop is an operational branch of a tenant. A cashier or manager assigned to Shop A cannot inspect Shop B's sales, stock, registers, or financial metrics unless granted explicit multi-shop permissions.
- A Register represents a checkout point tied to an active cash drawer and cashier shift.
- Monetary values are represented in `decimal(15, 2)` or integer cents. Floating-point arithmetic for currency is strictly prohibited across the entire stack.

---

## 3. Modular Monolith Architecture

BlePOS adopts a modular monolith structure:
- **Presentation Layer**:
  - Platform Console: Dedicated management interface under `/platform-console` running on the `platform` guard with dedicated session cookie (`PLATFORM_SESSION_COOKIE`), strict CSP with nonces, and mandatory MFA.
  - Tenant Web & POS: Lightweight, high-speed, keyboard-accessible interface running on the `web` guard with session cookie (`SESSION_COOKIE`), optimized for desktop, tablet, and touch terminals.
  - API Layer: RESTful JSON endpoints protected by token/session authentication, tenant scoping, rate limiting, and idempotency headers.
- **Service & Domain Layer**:
  - Encapsulates business logic, transactional integrity, and external integration boundaries.
  - Services handle complex business operations (e.g., `ProvisioningEngine`, `SaleService`, `StockMovementService`, `AccountingService`, `DarajaService`, `EtimsService`).
  - Presentation controllers remain thin, delegating to services and form requests.
- **Data & Persistence Layer**:
  - Central platform models: `PlatformUser`, `PlatformRole`, `PlatformPermission`, `PlatformSession`, `PlatformSecurityEvent`, `PlatformAuditLog`, `PlatformApproval`, `Tenant`, `TenantDomain`, `Plan`, `Subscription`, `Module`, `IndustryTemplate`, `ProvisioningRun`, `ProvisioningStep`.
  - Tenant operational models: `Shop`, `Register`, `Warehouse`, `User`, `Category`, `Product`, `Stock`, `StockMovement`, `Sale`, `SaleItem`, `Payment`, `CashierShift`, `Customer`, `Supplier`, `PurchaseOrder`, `Expense`, `Account`, `JournalEntry`.
  - Database triggers and cryptographic hash chains protect the audit trails against unauthorized tampering.

---

## 4. Multi-Tenancy Architecture

### 4.1 Tenancy Model
BlePOS implements a defense-in-depth isolation model:
1. **Routing & Context Resolution**:
   - Subdomain or path-based tenant identification resolved by `ResolveTenantContext` middleware.
   - Server-side identification: the authenticated tenant context is verified from user credentials and server sessions; client-supplied tenant IDs in request bodies are rejected.
2. **Global Scoping (`TenantScope`)**:
   - All tenant operational models use the `BelongsToTenant` trait.
   - Automatically filters all queries (`WHERE tenant_id = ?`) and populates `tenant_id` on model creation.
3. **Shop Scoping (`ShopScope`)**:
   - Models tied to specific branch locations implement `BelongsToShop`.
   - Cashiers and shop managers are locked to assigned shops via backend middleware and Eloquent scopes.
4. **Connection Isolation**:
   - Central database houses platform management and tenant registry.
   - Connection abstraction supports both single-database partitioned operations and dedicated tenant database connections for enterprise isolation.

---

## 5. Security & Authorization Boundaries

1. **Guard Separation**:
   - Platform control plane uses `platform` guard, authenticating against `platform_users`.
   - Tenant application uses `web` guard, authenticating against `users`.
   - Sessions, cookies, and tokens are completely non-interchangeable.
2. **Role-Based Access Control (RBAC)**:
   - Granular permissions (e.g., `sales.create`, `inventory.adjust`, `reports.profit.view`).
   - Platform roles: Super Administrator, Operations Administrator, Billing Administrator, Support Agent, Compliance/eTIMS Officer, Platform Auditor, Security Officer.
   - Tenant roles: Owner, Shop Manager, Cashier, Accountant, Inventory Manager, Procurement Manager, HR Manager, Auditor.
3. **Step-Up Authentication & Two-Person Approvals**:
   - Sensitive platform mutations require active step-up verification within a 5-minute window.
   - Irreversible actions (Super Administrator grants, MFA resets, tenant archival/deletion) require two-person authorization.
4. **Cryptographic Audit Log**:
   - Platform and tenant events record SHA-256 chained hashes (`previous_hash` + event payload = `entry_hash`).
   - Tamper-detection is verifiable via CLI (`php artisan platform:audit-verify`).

---

## 6. Integration Boundaries

1. **Safaricom M-Pesa (Daraja API)**:
   - STK Push (Lipa na M-Pesa Online), C2B callbacks, transaction status query, and reversals.
   - Per-shop credential storage (encrypted at rest), callback signature verification, duplicate callback idempotency, and offline reconciliation.
2. **KRA eTIMS (Electronic Tax Invoice Management System)**:
   - Direct integration via OSCU/VSCU API specifications for East African fiscal compliance.
   - Asynchronous document submission queue, retry mechanism, fiscal signature storage, and QR code generation for valid receipts.
3. **Hardware & Peripheral Support**:
   - Thermal receipt printers (ESC/POS 80mm and 58mm), barcode/QR scanners, customer-facing displays, and electronic cash drawers.
