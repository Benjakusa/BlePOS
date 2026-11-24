# BlePOS — Master Engineering Roadmap

## 1. Phased Delivery Roadmap

The engineering roadmap follows the 10-phase sequence defined in the Master Project Specification. Each phase represents a complete, verifiable vertical increment.

```
Phase 0: Baseline Audit & Architecture Verification [COMPLETED]
   │
Phase 1: Foundation, Multi-Tenant Model & Data Isolation [CURRENT]
   │
Phase 2: Tenant Lifecycle & Automated Provisioning Engine
   │
Phase 3: Core POS Terminal & Business Operations
   │
Phase 4: Platform Administration & Subscription Management
   │
Phase 5: Financial Ledgers & Double-Entry Accounting
   │
Phase 6: Industry Modules (Retail, Pharmacy, F&B, Hospitality, Fuel)
   │
Phase 7: East Africa Integrations (Safaricom M-Pesa & KRA eTIMS)
   │
Phase 8: Role-Based & Industry Dashboards
   │
Phase 9: Security Hardening, Performance & Disaster Recovery
   │
Phase 10: Production Deployment & Operational Handover
```

---

## 2. Phase Deliverables Breakdown

### Phase 0: Repository Audit & Baseline [VERIFIED]
- Audit existing Laravel 13 stack, dependencies, and test suite.
- Verify platform guard authentication, MFA, step-up, and audit hash chaining.
- Produce master documentation suite: Architecture, Security, Multitenancy, Modules, Dashboards, Integrations, Backups, Testing, Deployment, Roadmap.

### Phase 1: Foundation, Multi-Tenant Model & Data Isolation [IN PROGRESS]
- Central platform tenant models: `Tenant`, `TenantDomain`, `TenantUser`.
- Defense-in-depth isolation engine: `BelongsToTenant` trait and `TenantScope`.
- Branch/Shop isolation engine: `Shop`, `ShopScope`, `BelongsToShop`.
- Automated test coverage verifying cross-tenant and cross-shop data denial.

### Phase 2: Tenant Lifecycle & Provisioning Engine
- 30-step idempotent provisioning state machine (`ProvisioningRun`, `ProvisioningStep`).
- Resumable provisioning with safe failure recovery and duplicate prevention.
- Industry template seeding (Standard Retail, Pharmacy, Restaurant, Supermarket).
- Tenant status transitions: `provisioning`, `active`, `past_due`, `grace_period`, `suspended`.

### Phase 3: Core POS Terminal & Business Operations
- High-speed POS interface: Barcode scanning, cart calculation, discounts, split tender.
- Product catalogue: Items, categories, variants, SKU, price lists.
- Multi-shop inventory tracking, stock movements, stock adjustments, and transfers.
- Register management: Opening float, cashier shifts, cash drops, and shift closing reconciliation.
- Customers, suppliers, purchase orders, goods receipts, and expenses.

### Phase 4: Platform Super Administration & Commercial Plans
- Subscription plan versioning (`plans`, `plan_versions`, `plan_limits`).
- Tenant billing: Invoices, M-Pesa platform payments, credit notes, dunning.
- Module catalogue management and plan entitlement matrix.
- Break-glass emergency tenant access protocol with dual-authorization and audit logging.

### Phase 5: Financial Ledgers & Double-Entry Accounting [VERIFIED]
- Standardized Chart of Accounts (Assets, Liabilities, Equity, Revenue, Expenses).
- Real-time double-entry journal entries generated from POS sales, receipts, and expenses.
- Financial reporting: Trial Balance, Balance Sheet, Profit & Loss Statement, General Ledger.
- Automatic sales revenue, VAT payable, and COGS/Inventory journal posting with void reversal support.
- Operating expenses management with direct ledger impact.

### Phase 6: Industry Modules
- Pharmacy: Batches, expiry alerts, prescription management, restricted drug controls.
- Restaurant: Visual floor/table management, Kitchen Display System (KDS), recipe deduction.
- Hospitality: Room inventory, guest reservations, check-in/out, folios, RevPAR.
- Wholesale: Tiered pricing, minimum order quantities, customer credit limits, dispatch.
- Fuel Station: Storage tanks, pump meter readings, dip variance reconciliation.

### Phase 7: East Africa Integrations
- Safaricom Daraja 2.0 M-Pesa STK Push, callbacks, and automated till reconciliation.
- KRA eTIMS fiscalization engine: OSCU/VSCU signing, transmission queue, and QR code receipt generation.
- Hardware peripherals: Direct thermal ESC/POS printing, cash drawers, barcode scanners.

### Phase 8: Role-Based Dashboards
- Executive dashboards for Owner, Shop Manager, Cashier, Accountant, Inventory, and Procurement.
- Industry-specific operational widgets (Near expiry, Open tables, Tank dip levels).
- Zero fake data policy: All metrics powered by verified SQL aggregations.

### Phase 9: Security Hardening & Disaster Recovery
- End-to-end security audit and penetration resistance testing.
- Automated database backup and synthetic sandbox restore verification.
- Query performance profiling, indexing, and API p95 latency optimization.

### Phase 10: Production Deployment Readiness
- Production deployment runbooks, health probes, supervisor worker management, and SSL termination.
