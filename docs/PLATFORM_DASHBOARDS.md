# BlePOS — Platform & Tenant Dashboards

## 1. Overview

BlePOS implements distinct role-tailored dashboard perspectives across both the Platform Control Plane and Tenant Data Plane.

> [!IMPORTANT]
> All metrics and KPI counters are calculated from live, honest database aggregates. No fabricated or hardcoded metrics are permitted anywhere in the system.

## 2. Platform Control Plane Dashboard (`/platform-console`)

The central executive view for platform administrators, providing:
- **Tenant Overview**: Total tenants, active tenants, provisioning in-flight, suspended accounts.
- **Financial Metrics**: MRR (Monthly Recurring Revenue), ARR (Annual Recurring Revenue = 12 × MRR), ARPU, churn rate.
- **Infrastructure Health**: System status, database latency, queue backlogs, failed jobs.
- **Compliance Banner**: Real-time status of KRA eTIMS certification (`BLOCKED-ON-CERTIFICATION` placeholder state).
- **Security Events**: Recent login attempts, MFA challenges, and audit log activity.

## 3. Tenant Data Plane Dashboards

Tenant dashboards are strictly scoped to the authenticated user's tenant and assigned shop:

| Role Perspective | Primary Metrics & Widgets |
|---|---|
| **Cashier View** | Current register status, active cash drawer float, held carts, today's sales count. |
| **Shop Manager View** | Today's shop revenue, cash vs. digital payments, top selling items, low stock alerts. |
| **Tenant Owner View** | Multi-branch revenue comparison, gross margin, staff sales performance, inventory valuation. |
| **Industry Perspectives**| Healthcare (near-expiry batches), F&B (open table orders, active kitchen tickets). |

## 4. Key Controllers & Code References

- Platform Controller: `App\Http\Controllers\Platform\DashboardController`
- Platform View: `resources/views/platform/dashboard.blade.php`
- Tenant Controller: `App\Http\Controllers\Tenant\TenantDashboardController`
- Tenant View: `resources/views/tenant/dashboard.blade.php`
