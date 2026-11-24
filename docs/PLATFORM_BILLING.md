# BlePOS — Platform Billing & Subscriptions

## 1. Overview

BlePOS platform billing manages plans, tenant subscriptions, automated invoice generation, payment tracking, proration, and dunning workflows.

> [!NOTE]
> Platform billing is completely decoupled from tenant operational accounting. Subscription fees collected by BlePOS are recorded in platform billing tables and do not mix with tenant sales or shop ledgers.

## 2. Subscription Lifecycle

```
[Plan Selected] ──► [Trial / Active] ──► [Renewal Invoiced]
                           │                     │
                     (Payment Failed)      (Paid in Full)
                           │                     │
                           ▼                     ▼
                     [Grace Period]        [Active Renewed]
                           │
                     (Dunning Exhausted)
                           │
                           ▼
                      [Suspended]
```

## 3. Plans & Versioning

- Plans are versioned: existing tenant subscriptions are pinned to their agreement's version until explicit migration.
- Tiers: `Starter`, `Standard`, `Professional`, `Enterprise`.
- Currencies: Platform billing standard is `KES` (Kenyan Shilling), with multi-currency conversion support.
- Metrics: Monthly Recurring Revenue (MRR) and Annual Recurring Revenue (ARR = 12 × MRR) calculated dynamically.

## 4. Invoicing & Kenyan Tax Compliance

- Platform invoices record taxable base amounts, 16% standard VAT, and total payable.
- **eTIMS Integration State**: Fiscal invoice signing on platform invoices operates under a certified placeholder (`BLOCKED-ON-CERTIFICATION`). Live regulatory transmission is staged awaiting formal KRA sign-off.

## 5. Dunning & Grace Policy

- 3 automated payment retry attempts over 7 business days.
- 5-day grace period with banner notices before suspension.
- Reactivation is immediate upon invoice settlement.

## 6. Key Models & Code References

- Models: `App\Models\Plan`, `App\Models\Subscription`, `App\Models\Invoice`
- Service: `App\Services\PlatformAnalyticsService`
- Controller: `App\Http\Controllers\Platform\BillingController`
