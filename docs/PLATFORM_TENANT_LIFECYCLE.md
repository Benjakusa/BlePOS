# BlePOS — Platform Tenant Lifecycle

## 1. Lifecycle Overview

The tenant lifecycle manages the complete state machine of a tenant organization in BlePOS:

```
[Registration / Onboarding]
           │
           ▼
     [Provisioning] ──── (Failure) ───► [Provisioning Failed] (Retryable)
           │
           ▼
        [Active] ◄────────────────────────┐
       │   │   ▲                          │
       │   │   │                          │
       │   ▼   │                          │
       │ [Grace Period]                   │
       │       │                          │
       │       ▼                          │
       └──► [Suspended] ──────────────────┘
               │ (Reactivation)
               ▼
          [Archived] (Two-person approval required)
               │
               ▼
           [Deleted] (Retention policy & compliance export)
```

## 2. State Machine

| State | Allowed Operations | POS / Checkout Access | Data Access |
|---|---|:---:|:---:|
| `provisioning` | Automated setup steps running | Blocked | Blocked |
| `active` | Normal business operations | Allowed | Full |
| `grace` | Past-due warning period | Allowed | Full |
| `suspended` | Billing default, compliance freeze | Blocked (Read-only / Locked) | Read-only admin |
| `archived` | Soft-archived pending retention window | Blocked | Read-only Platform Console |
| `deleted` | Anonymized / purged per retention rules | Blocked | None |

## 3. Suspension & Reactivation Safeguards

- **Data Preservation Guarantee**: Suspending a tenant NEVER deletes database records, inventory counts, accounting journals, or audit logs.
- **Reactivation**: Restores immediate operational status once billing or compliance holds are cleared.
- **Archival**: Requires step-up authentication and two-person approval.
- **Audit Logging**: Every state transition generates an immutable entry in `platform_audit_logs`.

## 4. Key Models & Code References

- Model: `App\Models\Tenant`
- Migration: `database/migrations/2026_11_22_000001_create_tenants_table.php`
- Controller: `App\Http\Controllers\Platform\TenantController`
- Routes: `routes/web.php` (`platform.tenants.*`)
