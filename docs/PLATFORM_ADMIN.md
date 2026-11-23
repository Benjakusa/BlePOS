# BlePOS — Platform Administration & Control Plane Specification

## 1. Overview & Authority

The **Platform Control Plane** (`/platform-console`) provides centralized SaaS governance for BlePOS. It is completely isolated from the tenant operational plane, operating under its own authentication guard (`platform`), distinct session cookie, and security policies.

---

## 2. Platform Roles & Permission Namespace

### Built-in Platform Roles:
1. **Super Administrator (`super-administrator`)**: Unrestricted governance across platform configuration, tenant lifecycles, and security controls. Tenant operational business records remain barred except via audited break-glass procedures.
2. **Platform Operations Administrator (`platform-operations-administrator`)**: Manages tenant provisioning, retry runs, database allocation, system migrations, and infrastructure health. Barred from platform billing records and tenant secrets.
3. **Billing Administrator (`billing-administrator`)**: Manages subscription plans, tenant invoicing, M-Pesa/credit payment allocations, discounts, grace periods, and platform revenue metrics.
4. **Support Agent (`support-agent`)**: Resolves tenant support cases, inspects tenant metadata, sends invitations, and initiates approved break-glass workflows. Cannot view tenant transactional data or secrets.
5. **Compliance / eTIMS Officer (`compliance-etims-officer`)**: Monitors KRA eTIMS onboarding, integration certificates, error queues, and fiscal compliance logs.
6. **Platform Auditor (`platform-auditor`)**: Read-only access across platform audit trails, security events, revenue logs, and compliance records.
7. **Security Officer (`security-officer`)**: Manages platform MFA policies, reviews security anomalies, manages active sessions, locks compromised accounts, and approves break-glass requests.

---

## 3. Platform Console Navigation (18 Canonical Sections)

1. **Dashboard**: High-level platform KPIs (Tenant counts, MRR, provisioning status, system uptime).
2. **Tenants**: Directory of all registered tenants with filtering by industry, status, and plan.
3. **Tenant Registration**: Multi-step wizard for onboarding new commercial entities.
4. **Provisioning**: Provisioning run monitor, step-by-step progress, and safe retry controls.
5. **Subscriptions**: Active tenant subscriptions, renewal dates, and status transitions.
6. **Billing**: Invoices, payment receipts, dunning workflows, and credit notes.
7. **Plans**: Subscription tiers (Free, Starter, Professional, Enterprise) and feature limits.
8. **Modules**: Versioned module catalogue, dependency graphs, and activation states.
9. **Industry Templates**: Pre-configured industry blueprints (Tax, CoA, default workflows).
10. **Compliance & eTIMS**: Fiscalization health, device status, and KRA submission queues.
11. **Infrastructure & Health**: Worker health, queue depths, database sizes, Redis/cache probes.
12. **Security Center**: Failed logins, locked users, session review, and anomaly alerts.
13. **Platform Users**: Platform staff roster, role assignment, and invitation lifecycle.
14. **Support**: Tenant support tickets, communication logs, and incident records.
15. **Announcements**: Broadcast banners and system notifications delivered to tenants.
16. **Reports**: Platform financial summaries, cohort churn, and module adoption analytics.
17. **Audit Log**: Immutable, chained cryptographic audit feed with formula-safe CSV export.
18. **Platform Settings**: Branding, mail/SMS gateways, IP allowlists, and global policies.

---

## 4. Platform Super Administrator CLI Creation

The initial superadministrator account must be created via CLI to prevent exposure over public web routes:

```bash
php artisan platform:create-superadmin --name="System Administrator" --email="admin@blepos.co.ke"
```

Requirements:
- Secure, hidden password entry with strict complexity validation (minimum 12 characters).
- Automatically initializes TOTP setup requirements upon first web login.
- Duplicate creation is rejected if an active superadministrator already exists.
