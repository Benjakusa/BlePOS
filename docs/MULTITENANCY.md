# BlePOS — Multi-Tenancy & Data Isolation Specification

## 1. Overview & Principles

Multi-tenancy and data isolation form the foundational security requirement of BlePOS. Under no circumstances may data belonging to one tenant be read, modified, or inferred by another tenant.

### Core Principles:
1. **Server-Side Context Authority**: The authenticated tenant context is determined strictly on the server from the authenticated session and verified domain/subdomain mapping. Client-supplied tenant IDs in request headers, URL parameters, or JSON payloads are never trusted.
2. **Defense-in-Depth**:
   - Web Routing / Middleware: Validates that the user belongs to the active tenant domain.
   - Eloquent Scoping: Automatic global query filtering (`tenant_id = ?`) prevents accidental omissions in raw queries.
   - Model Creation Hooks: Automatically stamps `tenant_id` on model persistence.
   - Storage Isolation: Uploaded receipts, documents, and product images are stored in tenant-segregated directories (`storage/app/tenants/{tenant_id}/`).
   - Cache Namespacing: Cache tags and keys are prefixed with `tenant:{tenant_id}:`.
   - Asynchronous Queues: Jobs carry explicit tenant context and restore isolation boundaries before execution.

---

## 2. Tenant & Shop Hierarchy

```
Tenant (e.g. "Acme Supermarkets Ltd", KRA PIN: P051234567X)
├── Shop 1: "Acme Westlands Branch" (Code: ACM-WST)
│   ├── Register 1 (POS Terminal 1, Cash Drawer A)
│   ├── Register 2 (POS Terminal 2, Cash Drawer B)
│   └── Main Warehouse
└── Shop 2: "Acme CBD Branch" (Code: ACM-CBD)
    ├── Register 1 (POS Terminal 1)
    └── CBD Stockroom
```

### Hierarchy Rules:
- **Tenant Scope**: Governs all operational entities (Products, Customers, Suppliers, Invoices, Accounting Accounts).
- **Shop Scope**: Governs physical branch operations (Stock on Hand, Register Sessions, POS Sales, Cash Floats, Local M-Pesa Tills/Paybills, Local eTIMS Device Signatures).
- **Cross-Shop Isolation**: Staff assigned strictly to Shop 1 cannot query, view, or perform transactions in Shop 2.

---

## 3. Provisioning Engine & Lifecycle State Machine

### 3.1 Tenant Lifecycle States

```
[Registered] ──> [Provisioning] ──> [Provisioned] ──> [Active / Trial]
                       │                                    │
                       └──> [Provisioning Failed]           ├──> [Past Due]
                                   │                        │          │
                                   └──> [Retry / Abort]     ├──> [Grace Period]
                                                            │          │
                                                            └──> [Suspended]
                                                                       │
                                                                 [Archived]
                                                                       │
                                                                 [Deleted]
```

### 3.2 30-Step Provisioning Workflow
Every tenant onboarding executes through an idempotent, step-tracked provisioning run:
1. Validate tenant business identity (Legal Name, Trading Name, KRA PIN uniqueness).
2. Validate domain/subdomain availability and DNS formatting.
3. Validate selected subscription plan and billing cycle.
4. Validate module entitlements and dependency matrix.
5. Validate shop and user limit constraints.
6. Reserve unique tenant code and slug.
7. Record initial `ProvisioningRun` and generate execution manifest.
8. Allocate database target (Central DB partition or dedicated connection).
9. Initialize tenant database connection parameters.
10. Configure data isolation boundaries and tenant encryption key.
11. Apply base relational schema.
12. Execute tenant migrations.
13. Execute entitled module migrations.
14. Populate default tenant settings (Currency, Locale, Timezone).
15. Seed standard tenant roles (Owner, Shop Manager, Cashier, Accountant, etc.).
16. Populate tenant permission structure and assign default role capabilities.
17. Generate secure, hashed owner invitation token.
18. Create primary shop record and initial register.
19. Apply selected industry template (Tax categories, standard chart of accounts).
20. Configure initial module activation records.
21. Provision M-Pesa and payment integration placeholders.
22. Initialize KRA eTIMS onboarding state and certificate store.
23. Execute tenant database integrity probe.
24. Verify module dependency graph.
25. Verify data isolation enforcement via synthetic query test.
26. Verify shop records and register configuration.
27. Verify domain mapping and TLS readiness.
28. Record successful provisioning completion timestamp and metrics.
29. Dispatch welcome notification and invitation to the tenant owner.
30. Transition tenant status to `provisioned` / `trial`.

---

## 4. Platform Break-Glass Access Protocol

Platform operators and superadministrators are strictly barred from browsing tenant operational databases under normal operations. In catastrophic support incidents or legal emergencies:
1. **Request Submission**: Operator submits a `BreakGlassRequest` specifying: Tenant ID, Scope (Shop/Module), Specific Justification, and Requested Duration (maximum 60 minutes).
2. **Authorization**: Requires two-person approval (Security Officer + Tenant Owner or second Senior Administrator).
3. **Execution**: A time-limited, read-only session is generated. All viewed records, queries executed, and timestamps are logged to the immutable platform audit log and surfaced to the tenant owner's security feed.
4. **Automatic Expiry**: The session automatically terminates after the approved duration, revoking all tokens.
