# BLEPOS — ANNEX A (FINAL)
## Platform Control Plane, Super Administrator, Platform Console, Tenant Lifecycle, Billing, Security, Compliance, Support, and Role-Based Business Dashboards

**Relationship to the master prompt.** This annex is part of `BLEPOS_MASTER_PROMPT_v2`. It REPLACES Section 17 (SaaS administration and billing) and Section 17.1 platform-admin content in full, and EXTENDS Sections 5, 7, 8, 16 and 20. Where this annex is more specific than the master prompt, this annex wins. It does not replace the specifications for POS, inventory, M-Pesa, eTIMS, accounting or industry modules; it governs the platform layer above them and the dashboards that sit on top of them.

**Governing instruction.** You are building a real, secure, maintainable SaaS control plane, not an admin theme with buttons. Every screen must be connected to real backend functionality, real data, real authorization, real audit and real tests.

---

# PART 0: GOVERNING RULES

## 0.1 Scope

This annex covers: the platform control plane and console; the Platform Super Administrator; platform staff and roles; platform authentication and security; tenant onboarding, provisioning and lifecycle; module catalogue, industry templates and entitlements; subscriptions and platform billing; platform financial reports; infrastructure and tenant health; monitoring and alerting; security operations and audit; compliance and eTIMS oversight; M-Pesa ecosystem oversight; support tools; announcements; data protection, backup, restore and disaster recovery; and all tenant-side role dashboards (owner, shop manager, cashier, accountant, inventory, procurement, HR, auditor, industry).

## 0.2 Every feature is a full vertical slice

For every feature implement: database structure; backend service; validation; authorization and permission enforcement; API or server action; frontend interface; loading, empty, error and success states; audit events; tests; documentation; and operational monitoring where relevant. Forbidden: hardcoded data simulating functionality; "Coming soon" buttons for required features; claiming completion when only the interface exists.

## 0.3 Status labels (use consistently everywhere)

- **VERIFIED** — implemented and tested, with evidence recorded.
- **IMPLEMENTED-UNVERIFIED** — implemented, insufficiently tested.
- **PARTIAL** — some required functionality exists; workflow unfinished.
- **BLOCKED** — a dependency or external requirement prevents progress (state what is needed).
- **NOT STARTED** — no meaningful implementation.
- **FAILED** — attempted implementation or testing failed and needs correction.

Every status entry must list file paths, database tables, backend service/controller/job, test coverage, and what remains. A screen that renders is not automatically VERIFIED.

## 0.4 No fake data

Every figure, chart, counter, trend, percentage, status and alert must come from real application records, real platform configuration, real health probes, real audit records, real scheduled metrics, or seeded test data in test environments only. If a source is unavailable show one of: `Unavailable`, `Not configured`, `No data`, `Data source unavailable`, plus `Last updated: <timestamp>`. Never silently substitute zero for a failed query: zero and unavailable are different. A missing monitoring source must never display "Healthy".

## 0.5 Security-first

The console must never become a shortcut into tenant operational databases. All access passes through platform authentication, platform permissions, server-side authorization, tenant boundaries, shop scope where relevant, purpose restrictions, audit, rate limits and session controls. No frontend-only permission checks. No hidden routes that bypass authorization. Being Super Administrator does not grant access to tenant business data.

## 0.6 Design philosophy

The console is a powerful operational workspace: lightweight, fast, responsive, accessible, consistent, information-dense without clutter, desktop-first and usable on tablets. Avoid oversized cards, gradients, glassmorphism, heavy shadows, decorative charts and unnecessary animation. Prioritise alerts, actionable information, operational visibility and drill-down.

## 0.7 Delivery tiers for this annex (build order discipline)

This annex is large. Build it in tiers; do not start a later tier before the earlier tier is VERIFIED.

- **Tier A (foundation, build first):** platform guard, authentication, MFA, sessions, platform users and roles, permission catalogue, audit chain, security events, tenant registry, tenant list and detail (core tabs), provisioning engine, tenant lifecycle (suspend/reactivate), basic plans and subscription assignment, break-glass, step-up/two-person approvals.
- **Tier B (commercial and modules):** full plan versioning, platform billing, invoices (including eTIMS fiscalisation of platform invoices), payments, dunning, module catalogue, entitlements, industry templates, announcements, support cases.
- **Tier C (operations and compliance):** infrastructure monitoring, queues, scheduler, backups and restore workflow, migrations console, security centre extras (IP rules, key management), compliance and eTIMS oversight, data-protection requests, legal documents.
- **Tier D (analytics and dashboards):** platform analytics, platform dashboard, tenant dashboards delivered alongside their domain modules.

A dashboard widget is built together with the data source it reads, never before. A widget whose source does not exist yet is NOT STARTED and must not appear in the UI.

## 0.8 Mapping to master prompt phases

| Annex tier / phase | Master prompt phase |
|---|---|
| A1 Security foundation, A2 Users/roles | Phase 3 (identity and security foundation) |
| A3 Tenant management, A4 Provisioning | Phase 2 (tenancy) and Phase 4 (platform admin) |
| A5 Billing (basic plans and assignment) | Phase 4; full billing in Phase 14 |
| A6 Modules and templates | Phase 5 |
| A7 Infrastructure monitoring | Phase 4 (basic), Phase 19 (hardening) |
| A8 Security centre | Phases 3–4, extended in Phase 19 |
| A9 Compliance and eTIMS console | Phase 9 (eTIMS) |
| A10 Support and announcements | Phase 15 (notifications) |
| A11 Analytics | Phase 18 |
| A12 Platform dashboard | Phase 4 (incremental), completed after sources exist |
| A13 Tenant dashboards | Built inside each domain phase (6–18) |
| A14 Hardening and release readiness | Phases 19–20 |

---

# PART 1: PLATFORM CONTROL PLANE ARCHITECTURE

## 1.1 What the control plane is

The control plane is the central administrative layer of BlePOS. It manages the SaaS itself, independently of tenants' business activity: platform identity, staff, roles, permissions; tenant registration, lifecycle, provisioning, databases, domains; plans, billing, licensing; module catalogue, templates, entitlements; platform settings; health, infrastructure, security, audit; support, compliance, data protection, communications; analytics; backup and recovery; deployment and migration visibility. It is architecturally separated from the tenant business application.

## 1.2 Control plane vs tenant data plane

- **Control plane** (central database): platform users and authentication records, roles and permissions, platform sessions, tenant registry and status, provisioning metadata, database-server assignment, domains, subscriptions, plans and versions, overrides, invoices and payments, module catalogue and versions, dependencies, entitlements, templates, provisioning runs and steps, usage snapshots, platform audit, security events, break-glass records, announcements, feature flags, legal documents and acceptances, compliance metadata, backup metadata, health checks, metrics snapshots, platform integrations and settings.
- **Tenant data plane** (one isolated database per tenant): shops, registers, cashiers, products, variants, inventory, stock movements, sales, returns, customers, suppliers, purchase orders, expenses, payments, M-Pesa transactions, eTIMS documents, accounting, HR, pharmacy, hotel, restaurant and other industry data.

Platform dashboards may use controlled, privacy-preserving aggregates and metrics snapshots only. The console MUST NOT provide unrestricted SQL, database browsing, raw tenant table inspection or direct business record access.

## 1.3 Tenant and shop hierarchy

Platform → Tenant (company: its own database, subscription, modules, policies) → Shops (each with registers, staff, stock, M-Pesa and eTIMS configuration, operations). A tenant's subscription determines capacity and module entitlement. Each shop can have its own: shop code, physical location, county/region, contacts, business hours, tax configuration, **eTIMS branch/device configuration**, **M-Pesa configuration**, registers, cashiers, manager, stock, warehouse, pricing policy, discounts, receipt configuration, targets, expense categories, cost centres, reports. The system supports tenant-wide and shop-specific reports, shop comparisons, restricted shop access, shop switching, shared and shop-specific catalogues, inter-shop transfers with approvals, and shop-specific M-Pesa settlement and reconciliation.

## 1.4 Security boundary (mandatory attack-scenario tests)

Prevent and test: tenant session reuse in the console; platform session reuse on tenant domains; cross-tenant database connection leakage; cache leakage; queued-job cross-execution; file access; export access; report leakage; search leakage; payment-callback misrouting; webhook leakage; shop-scope bypass; module-entitlement bypass; permission bypass via direct API calls.

---

# PART 2: PLATFORM AUTHENTICATION AND SECURITY

## 2.1 Separate authentication system

Own platform user model and table (central DB); own guard; own login/logout flow; own session cookie name and session configuration; own middleware stack; own password-reset flow; own MFA enrolment and recovery; own audit events. Console URL: `admin.<blepos-domain>` (preferred) or `<blepos-domain>/platform-console`; document and justify the final choice. Tenant authentication must never authenticate a platform user and vice versa.

## 2.2 Login

Email or username, password, TOTP, account-status validation, rate limiting, failed-login tracking, IP risk detection, device info capture, login alerts, session issuance and revocation, MFA recovery, security-event recording. No public registration. No self-registration. Accounts are created by controlled invitation or CLI only.

## 2.3 MFA

Mandatory for all platform staff. TOTP enrolment with QR, confirmation before activation, hashed one-time recovery codes, audited MFA reset, MFA enforced at first login and after account recovery, optional WebAuthn/passkeys. MFA must not be bypassable via direct API calls, alternate endpoints, password reset, session reuse, remember-me cookies or recovery routes.

## 2.4 Creating the first Super Administrator

Only via server CLI: `php artisan platform:create-superadmin`. It requires server access, prompts for identity, validates email, accepts the password securely (not via shell history or logs), enforces the password policy, assigns Super Administrator, requires MFA enrolment at first login, records the event, and refuses to create another initial Super Administrator unless a separate audited recovery operation is used (requires reason, server-level access, immutable security audit event, documented procedure). Never ship default credentials; never hardcode any.

## 2.5 Session security

Absolute lifetime 8 h; idle timeout 30 min; no remember-me; secure, HttpOnly, appropriate SameSite cookies; session rotation after authentication and after privilege change; revocation on account disable, MFA reset and (where appropriate) password change; concurrent-session visibility; per-session and all-session forced logout. Administrators see: user, login time, last activity, device/browser, IP, approximate location (labelled as estimated), expiry, MFA status.

## 2.6 Login protection

Per-IP and per-account rate limits, progressive lockout (avoid permanent lockout that enables denial of service), brute-force and credential-stuffing detection, challenge escalation only when justified, suspicious-login alerts, lock notifications, password-reset abuse protection, MFA failure tracking, session anomaly detection.

## 2.7 Network hardening (configurable)

IP allowlist, VPN-only access, private network access, reverse-proxy trust configuration, HTTPS enforcement, HSTS, strict security headers, separate console rate limits, firewall guidance, optional geo-restriction. Allowlist/VPN settings must not allow accidental lock-out of all administrators (require a tested emergency CLI override).

## 2.8 Step-up authentication

Password revalidation plus MFA before sensitive operations. Server-enforced, short validity window, action-specific where appropriate, auto-expiring, logged, invalidated after relevant security changes.

## 2.9 Platform user controls

Invite, resend/cancel invitation, deactivate/reactivate, lock/unlock, reset MFA via approved process, require password reset, revoke sessions, change role, review login history, security events and failed logins. All sensitive actions audited.

---

# PART 3: PLATFORM ROLES, PERMISSIONS AND APPROVALS

## 3.1 Permission architecture

Granular names, `platform.<area>.<action>`. Initial catalogue (not exhaustive; generate the full catalogue from every implemented action and screen):

```
platform.dashboard.view
platform.tenants.view / create / update / suspend / reactivate / archive / delete.request
platform.provisioning.view / retry / abort
platform.billing.view / plans.manage / subscriptions.manage / invoices.manage / payments.manage / refunds.approve
platform.modules.view / manage
platform.entitlements.grant / revoke
platform.security.view / users.manage / roles.manage / sessions.revoke
platform.audit.view / export
platform.support.view / announcements.manage
platform.compliance.view / etims.manage
platform.infrastructure.view / jobs.manage / backups.view / restore.request
```

## 3.2 Built-in roles

**A. Super Administrator** — platform owner. CAN: view the dashboard; manage platform staff, roles and permissions; manage tenant lifecycle, registration and provisioning; manage subscriptions, plans and pricing; manage modules, templates and entitlements; configure integrations and global settings; manage security policies; approve designated high-risk actions; review all platform audit; review health; initiate approved recovery. CANNOT: read tenant operational records without break-glass; view plaintext payment, M-Pesa or eTIMS credentials; alter or delete audit records; disable mandatory auditing; bypass tenant isolation; bypass approval requirements.

**B. Platform Operations Administrator** — CAN: view tenants; provision, retry and investigate provisioning; run approved migration workflows; view database/infrastructure/queue/failed-job/backup health; manage approved maintenance; suspend/reactivate under assigned authority; review deployment and migration status; view eTIMS/M-Pesa onboarding status (not credentials). CANNOT: change pricing or approve refunds; modify Super Administrator accounts; access tenant business data or secrets.

**C. Billing Administrator** — CAN: create/edit plans and versions; manage subscriptions; generate invoices; record and reconcile payments; issue credit notes; manage discounts, grace periods and dunning; view revenue reports; manage renewals. CANNOT: provision tenants; modify tenant databases; access tenant transactions; change security roles; access secrets.

**D. Support Agent** — CAN: search tenants; view tenant metadata (status, plan, subscription state, entitlements, shop and user counts, onboarding progress, recent activity metadata); resend invitations; trigger password-reset emails; request break-glass; create cases and notes; send permitted communications. CANNOT: view sales, customers, stock or payment contents; modify tenant operational records; modify subscriptions or plans; suspend tenants without assigned permission; impersonate tenants.

**E. Compliance / eTIMS Officer** — CAN: view eTIMS onboarding, shop-level status, device configuration metadata, integration status, queue health, rejection rates, sanitised errors; manage certification checklists, evidence and KRA correspondence; review data-protection requests. CANNOT: view invoice contents or payment credentials; manage billing; modify tenant operational data.

**F. Platform Auditor** — read-only. CAN: view audit, security events, role/permission changes, tenant lifecycle and subscription events, platform reports; export approved audit data; verify audit integrity. CANNOT: change any record, manage users, approve actions, provision, or alter audit.

**G. Security Officer** — CAN: review security events and login anomalies; review MFA compliance and sessions; revoke sessions; lock accounts; approve break-glass; review break-glass activity; approve key rotation; review security policies and vulnerability/dependency status. CANNOT: manage billing; alter tenant business records; access tenant data without break-glass.

**H. Custom roles** — built from explicit permissions; named and described; permission diff shown before saving; change history; audited; immediately enforced; no implicit wildcards; no access to restricted functions unless explicitly granted. Built-in roles cannot be silently deleted or weakened.

## 3.3 Permission matrix

Generate a matrix covering every sidebar item, page, API endpoint, action, export, bulk operation, sensitive setting, approval, background operation, tenant intervention and report. Permissions are enforced in the service/action layer, not only at routes.

## 3.4 Role changes

Require: permission to manage roles, step-up auth, before/after diff, mandatory reason, audit event, notification to the affected user, immediate enforcement, session revocation when privilege is reduced or security is affected. Granting Super Administrator requires two-person approval.

## 3.5 Step-up, two-person approval and delay matrix

| Action | Step-up | Two-person approval | Mandatory delay |
|---|---|---|---|
| Create/modify platform users or roles | Yes | Only for granting Super Administrator | — |
| Suspend tenant manually | Yes | No | — |
| Suspend for non-payment | No (automated by billing policy; audited; reversible) | No | Per dunning schedule |
| Archive tenant | Yes | Yes | 14 days |
| Delete tenant data | Yes | Yes | 14 days + verified backup + legal-hold check |
| Rotate master key / re-encrypt | Yes | Yes | — |
| Approve break-glass | Yes | Yes (two platform approvers) or tenant-owner approval | — |
| Bulk plan change above configured count | Yes | Yes | — |
| Credit notes/refunds above threshold 1 | Yes | No | — |
| Credit notes/refunds above threshold 2 | Yes | Yes | — |
| Reset another admin's MFA | Yes | Yes | — |
| Tenant restore into production | Yes | Yes | After isolated test restore |
| Change platform payment/eTIMS credentials | Yes | No | — |
| Change IP allowlist | Yes | Yes if it affects all admins | — |
| Disable audit streaming or alerts | Not possible; no such feature | — | — |

Approvers must differ from the requester. Delayed actions can be cancelled during the delay. All of these are enforced server-side and tested against direct API calls.

---

# PART 4: CONSOLE NAVIGATION AND INFORMATION ARCHITECTURE

## 4.1 Sidebar

1. Dashboard
2. Tenants
3. Provisioning
4. Subscriptions & Billing
5. Modules & Templates
6. Compliance
7. Infrastructure & Health
8. Security
9. Support
10. Reports & Analytics
11. Audit Log
12. Platform Settings

Items are permission-aware, responsive, collapsible, show unresolved-alert counts where relevant, retain page state. Forbidden items do not render; direct access returns 403.

## 4.2 Global header

Branding, breadcrumbs, global search, quick actions, notifications, platform health indicator, help link, user menu, security status, **environment indicator** (Development / Testing / Staging / Production; production must never be confusable with testing).

## 4.3 Global search

Platform metadata only: tenants, tenant codes, domains, owner email, platform users, subscription IDs, invoice numbers, provisioning run IDs, module names, support case IDs, audit event IDs, shop metadata where permitted. Tenant business data (sales, customers, products, transactions) is never searchable here.

## 4.4 Quick actions

Permission-controlled: register tenant, invite platform user, create plan, review failed provisioning, view failed jobs, view security events, view overdue invoices, view backup failures, review break-glass requests. Same authorization and audit as the full workflow.

---

# PART 5: PLATFORM DASHBOARD

## 5.1 Principles

The dashboard answers: How is BlePOS performing? How many tenants and how many active? Is onboarding succeeding? Are customers paying? Is revenue growing? Which subscriptions are at risk? Which modules are popular? Is the system healthy? Are there security or compliance risks? What needs attention now? Every widget has a real source, permission gate, defined calculation, date range, timestamp, drill-down, loading/empty/failure states, independent loading, documented caching, and never exposes unauthorised tenant detail.

## 5.2 Global controls

Date ranges: Today, Yesterday, 7d, 30d, 90d, 12 months, current and previous financial year, custom. Currency display, refresh, last-updated timestamp, optional auto-refresh. Widgets reload independently.

## 5.3 Row A — Headline KPIs

**Tenants:** total registered; active; trial; trials expiring in 7 and 30 days; pending registrations; pending provisioning; provisioning failures (red when above zero); suspended (split by reason); archived; cancelled; awaiting activation; failed onboarding; no first login; no first sale; at risk of churn.
**Subscriptions:** active; trial; past due; in grace; expiring; cancelled; renewals due; failed payments; upgrade and downgrade requests.
**Financial:** MRR; ARR; net new MRR; expansion; contraction; churned; reactivated MRR; invoices issued; invoices paid; outstanding receivables; collection rate; failed payment amount; refunds; credit notes.
**Operational:** total shops; registered tenant users; active users (30 days); active registers; offline devices; warehouses; API usage; storage usage; transactions processed (aggregate count only); aggregate payment volumes by method.
No KPI shows a tenant's private transaction detail.

## 5.4 Row B — Growth and lifecycle

New registrations over time; cumulative growth; active growth; trial conversion; trial extensions; churn and cancellation reasons; reactivation; average onboarding duration; median time to first login, first product, first sale and paid subscription.

**Activation funnel** (each stage defined by an explicit event): Registered → Provisioned → Owner invited → First login → Shop configured → First product → First sale → Paid subscription. Show totals, conversion %, drop-off %, median elapsed time, cohort and date range.

**Cohort retention:** monthly signup cohorts, months 0, 1, 2, 3, 6, 12, retained-tenant % and revenue retention where meaningful.

## 5.5 Row C — Subscriptions and revenue

MRR waterfall; ARR trend; revenue by plan, industry, region, cohort; tenants by plan; trial conversion; upgrade/downgrade trends; churn; reactivation; upcoming renewals and renewal failure rate; overdue invoices; collections; outstanding debt; ageing receivables; forecast (with stated assumptions); plan-limit pressure (tenants at 80 percent or more of any limit).

**MRR rules (document and test):** exclude one-time and setup fees, taxes, subscriptions beyond effective cancellation, and unpaid invoices treated as earned. Define treatment of annual vs monthly plans (annual divided by 12), discounts, free trials, grace periods, paused subscriptions, upgrades, downgrades and currency conversion. MRR and ARR must be mathematically consistent (ARR = 12 × MRR) and independently testable.

## 5.6 Row D — Modules and industries

Adoption heatmap; modules by tenant count and active usage; by industry; revenue contribution; activation rate and trends; failed activations; unresolved dependencies; modules approaching deprecation; module versions deployed; tenants on deprecated versions; entitlement exceptions; tenants exceeding plan module limits. Industry distribution (retail, pharmacy, hardware, restaurant, bar, hotel, wholesale, logistics, salon, fuel, others) is extensible.

## 5.7 Row E — Operational health

Monitor web app, API, PHP runtime/FPM, central DB, tenant DB servers, Redis, cache, queue workers, scheduler, file/object storage, email, SMS, payment gateway, Daraja/M-Pesa connectivity, KRA eTIMS connectivity, webhook delivery, background jobs. Each shows status (Healthy / Degraded / Down / Unknown / Not configured), latency, last success, last failure, failure count, error category, current incident, recovery status.

## 5.8 Row F — Queues and jobs

Pending, processing, completed, failed, failed in 24 h, retries, oldest pending age, long-running jobs, throughput, jobs by queue/tenant/type, success rate. Details are sanitised: never raw business payloads, secrets, customer data or full transaction contents. Actions (permissioned, audited): retry, retry after correction, mark investigated, view sanitised exception, view execution metadata, escalate.

## 5.9 Row G — Scheduler

Scheduled tasks, last and next run, duration, result, failed, missed and overlapping runs, disabled tasks, heartbeat. Manual execution is permissioned and audited.

## 5.10 Row H — Databases

Central: size, growth, connections vs limit, utilisation, slow queries, lock waits, deadlocks, backup status, migration version. Tenant: server, count per server, size, growth, connection utilisation, schema version, migration lag, backup and restore-test status, top databases by size, tenants nearing capacity, schema mismatches. Metadata only.

## 5.11 Row I — Application performance

Request count, 4xx and 5xx rates, API error rate, p50/p95/p99 latency, slow endpoints, webhook latency/failures, query latency, queue delay, memory, CPU, disk, network. Metrics come from a real provider; if none is configured show "Not configured".

## 5.12 Row J — Capacity

Disk usage and growth, memory, CPU, DB capacity and connection limits, queue, cache, storage, backup capacity, network. Thresholds and alert escalation. Forecasts only when data is sufficient.

## 5.13 Row K — Backup and disaster recovery

Last successful central and tenant backups, schedule, duration, size, failures, retention, destination, encryption status, integrity verification, last restore test and result, measured recovery time and recovery point. A backup file existing is not proof of validity; only a passed restore test is.

## 5.14 Row L — Compliance overview

eTIMS: tenants and shops onboarded, awaiting configuration, shops with rejected submissions, stalled queues, accepted/failed/pending counts, retry backlog, average latency, BlePOS certification status, integration version, checklist progress. Data protection: open, awaiting response, near deadline, overdue, completed, requiring legal review.

## 5.15 Row M — M-Pesa ecosystem

Shops configured, sandbox vs production, active, incomplete; callback success/failure rates; validation failures (possible forgery); reconciliation backlog; pending payments; timeout and failure rates; provider reachability; callback latency; recent configuration changes. Never expose consumer secrets, passkeys, access tokens, security credentials, full payment payloads or customer phone numbers. Platform M-Pesa credentials are distinct from tenant and shop credentials.

## 5.16 Row N — Security and risk

Failed platform logins, MFA failures, locked accounts, suspicious sessions, new-device logins, privilege and role changes, password-reset anomalies, incidents, break-glass requests and sessions, key rotation status, encryption status, vulnerability and dependency advisories, rate-limit violations, blocked IPs, callback abuse signals. Tenant anomaly signals (failed-login spikes, refund and void spikes, discount abuse patterns, stock-adjustment spikes, excessive exports, repeated M-Pesa callback failures, unusual API usage) are rule-based indicators for review, not accusations.

## 5.17 Row O — Operations feed

Chronological events (registration, provisioning and failures/retries, subscription changes, collections and failed payments, suspension, reactivation, module grants and revocations, plan and domain changes, migrations, backups and restore tests, incidents, break-glass, deployments, feature flags, announcements), each linking to its detail page where permitted.

## 5.18 Row P — Needs-attention inbox

Priority-ranked items: provisioning failures, tenant migration failures, DB connectivity problems, tenants awaiting activation, failed payments, overdue subscriptions, grace periods expiring, backup failures, failed restore tests, stalled eTIMS submissions, M-Pesa reconciliation backlog, failed email/SMS delivery, queue congestion, scheduler failures, security incidents, MFA gaps, error spikes, capacity warnings, certificates nearing expiry, data-protection deadlines, pending approvals, break-glass requests. Each has priority, category, severity, age, owner, status, next action, due date, escalation, acknowledgement and resolution notes.

---

# PART 6: TENANT MANAGEMENT

## 6.1 Tenant list

Columns: business name, tenant ID and code, owner name/email/phone (masked per permission), status, industry, plan, subscription status, billing cycle, trial end, renewal date, module count, shop count and limit, user count and limit, register count and limit, storage used, database server, database size, schema version, eTIMS status, M-Pesa status, provisioning status, last login, last activity timestamp, created date, health indicator.

## 6.2 Filters

Status, industry, plan, subscription, billing cycle, trial status and expiry, module, region, county, created date, renewal date, eTIMS status, M-Pesa status, database server, provisioning status, health, unresolved alerts, failed payment, failed provisioning, inactive tenants.

## 6.3 List capabilities

Server-side pagination, filtering, sorting, search; column chooser; saved filters; CSV export (permissioned, audited, minimal personal data); bulk and row actions; activity timeline.

## 6.4 Bulk actions

Send announcement or email; extend trial; change plan; add/remove tags; suspend; reactivate; request health check; request backup; export tenant metadata. Each bulk action: previews affected tenants, validates each, shows exceptions, requires a reason where appropriate, confirms, executes safely, writes one audit record per tenant, and reports per-tenant success or failure. Failures are never silently skipped.

## 6.5 Registration wizard

1. **Business identity:** legal name, trading name, tenant code, registration number, **KRA PIN (unique per tenant)**, VAT registration status, country, county, city, addresses, business email and phone, owner name/email/phone, industry, time zone, currency, locale.
2. **Industry:** loads a template; the template never silently activates unentitled modules.
3. **Modules:** available, included, required, dependencies, optional, unavailable under plan, requiring payment, conflicts, activation prerequisites; invalid selections are blocked.
4. **Subscription:** plan, billing cycle, trial dates, currency, pricing, discount, limits (shops, users, registers, warehouses, storage, API, offline devices, modules). Overrides need permission and a reason.
5. **Initial shops:** name, code, branch, location, county, address, phone, email, time zone, tax details, KRA branch ID if known, M-Pesa onboarding option, default register. Shops never share payment credentials.
6. **Domain:** subdomain, custom domain, availability, DNS verification, TLS status, primary domain, redirect rules; validated before provisioning.
7. **Owner invitation:** one-time hashed token, expiry, resend, revoke. No initial password is generated or shown to platform staff.
8. **Review:** identity, industry, shops, modules, plan, limits, domain, owner, provisioning requirements, warnings, costs.
9. **Provision:** starts a background workflow, never one uncontrolled request, and navigates to live status.

Validation includes disposable-email check, domain uniqueness, PIN uniqueness and plan limits.

## 6.6 Tenant detail tabs

Overview; Identity and contacts; Shops; Provisioning; Database and infrastructure; Subscription; Billing; Modules and entitlements; Limits and usage; Compliance (eTIMS state per shop without invoice contents, data-protection requests, legal acceptances); Security (MFA adoption, tenant security events, session and locked-user counts, force-logout, require password reset, lock/unlock; all audited and visible to the tenant owner); Support; Communications; Audit; Activity; Settings; Danger zone. Every tab respects permissions. Internal notes are platform-only and audited.

---

# PART 7: TENANT LIFECYCLE AND PROVISIONING ENGINE

## 7.1 Lifecycle state machine

States: pending registration; provisioning; provisioning failed; provisioned; invitation pending; trial; active; past due; grace period; suspended; cancellation pending; cancelled; archived; deletion pending; deleted. Typical path: Registered → Provisioning → Provisioned → Invited → Trial/Active → Past due → Grace → Suspended → Cancellation pending → Cancelled → Archived → Deletion pending → Deleted. Transitions are explicit and validated; invalid direct transitions are rejected; every transition is audited.

## 7.2 Provisioning workflow (steps)

1 validate tenant data; 2 validate domain; 3 validate plan; 4 validate module entitlement; 5 validate shop limits; 6 reserve tenant identifier; 7 record provisioning run; 8 allocate database server; 9 create tenant database; 10 configure database isolation; 11 apply base schema; 12 apply tenant migrations; 13 install module migrations; 14 create initial tenant configuration; 15 create default roles; 16 create tenant permission structure; 17 create owner invitation; 18 create initial shops; 19 create industry configuration (including tax profile and chart of accounts); 20 configure initial module activation; 21 create payment-configuration placeholders; 22 set eTIMS onboarding state; 23 verify tenant database; 24 verify module dependencies; 25 verify tenant isolation; 26 verify shop records; 27 verify domain mapping; 28 record completion; 29 send owner invitation; 30 mark provisioned.

Each step records: step ID, status, start and end time, duration, attempt count, error category, sanitised error, retry policy, idempotency, checkpoint.

## 7.3 Failure handling

Support retry from the failed step, safe full retry, idempotent operations, duplicate-request protection, database cleanup only where explicitly safe, manual intervention, abort, recovery from partial provisioning. A failure never leaves a tenant Active. A retry never duplicates tenants, domains, shops, users, permissions, migrations, module activations or billing records. A run can never be "forced active".

## 7.4 Provisioning screens and actions

Dashboard of runs: tenant, run ID, current step, progress, started, duration, status, attempts, failure category, retry availability, operator, database server, last update. Actions: retry, retry failed step, view sanitised logs, mark investigated, assign operator, add note, abort (only if no data created; otherwise archive path), escalate, run integrity check. No action bypasses the state machine.

## 7.5 Suspension

Reasons: non-payment, security, policy violation, customer request, administrative, legal, maintenance. Suspension is non-destructive: preserves the database and records and audit; blocks new operational activity per policy and unauthorised login; keeps billing and recovery workflows available; supports approved export; shows a suspension message; records reason, actor, time and review date.

## 7.6 Reactivation

Validate suspension reason, subscription state, security state and tenant integrity; restore permitted access, modules and shop access; record the event; notify the owner.

## 7.7 Archive

Requires step-up, reason, two-person approval, default 14-day delay, verified backup. Disables operational access, preserves data per retention policy and audit history, and is reversible before final deletion.

## 7.8 Deletion

Never a normal button. Flow: request with reason → approval → two-person approval → cooling-off period → data-retention review → legal-hold check → subscription closure → backup verification → export confirmation → domain release policy → database destruction plan → final confirmation → audit. Destruction is an explicit, documented, irreversible operation. Legally required financial and tax records follow the retention policy.

---

# PART 8: SUBSCRIPTIONS, BILLING AND COMMERCIAL MANAGEMENT

## 8.1 Plans

Fields: plan ID, code, name, description, visibility, status, currency, monthly and annual price, billing interval, trial duration, grace period, renewal and cancellation policy, included and add-on modules, limits (shops, users, registers, warehouses, API, storage, offline devices, reports), support level, industry restrictions (only if justified), feature flags, effective date, version. Types: free, trial, paid, custom enterprise.

## 8.2 Plan versioning

Plan changes never silently alter existing subscriptions. Maintain versions, effective dates, price/limit/module history, migration policy for existing subscriptions, renewal application policy and customer notification policy. A plan in use cannot be deleted, only archived.

## 8.3 Subscriptions

Tenant, plan, plan version, billing cycle, status, start, trial end, renewal date, grace end, cancellation date and reason, discount, override, currency, amount, payment history. Validated state machine: trial → active → past_due → grace → suspended → cancelled/expired.

## 8.4 Subscription actions

Assign, upgrade, downgrade, renew, cancel immediately or at period end, reactivate, extend trial, apply discount, add credit, change billing cycle, set grace period, suspend under billing policy. Each shows a preview (including proration) before execution, requires a reason, and uses step-up above thresholds.

## 8.5 Platform billing

Invoice generation and numbering, line items, tax calculation, payment records and allocation, overpayments, refunds, credit notes, failed payments and retry, reconciliation, ageing receivables, revenue reports. Platform billing is separate from tenant sales and tenant accounting; platform revenue never mixes with a tenant's ledger.

## 8.6 Platform payment methods

M-Pesa, bank transfer, card and other gateways, configurable. Platform payment credentials are separate from all tenant and shop configurations; encrypted, masked, write-only, rotatable, audited, tested through a controlled process.

## 8.7 Platform invoices and eTIMS

Invoices carry: number, tenant, billing period, subscription, line items, discounts, taxes, amount, currency, due date, status, payment history, PDF, email delivery, credit note, void rules. Numbering is consistent and auditable. **Platform invoices to Kenyan tenants are fiscalised through eTIMS under the platform company's own PIN and device configuration (separate from every tenant's eTIMS setup); credit notes are issued through eTIMS credit-note flows.** Platform eTIMS status is shown in Platform Settings and the compliance console, and its production use remains BLOCKED-ON-CERTIFICATION until BlePOS's own integration is certified.

## 8.8 Collections

Track invoiced, collected, outstanding, collection rate, failed-payment rate, refund rate, credits, overpayments, allocations, ageing receivables.

## 8.9 Dunning

Configurable schedule, for example: 7 days before due, 1 day before, due date, 1, 3 and 7 days overdue, grace notification, suspension warning, suspension. Each event has template, trigger, recipient, channel, status, retry and audit. Automated suspension is reversible on payment; a tenant is never suspended twice by duplicate billing events.

## 8.10 Revenue reports

MRR, ARR, ARPA, LTV estimate, churn rate, net and gross revenue retention, new, expansion, contraction, reactivation, refunds, collections, tax collected, revenue by plan, industry and region, forecast. Calculations are documented and tested.

---

# PART 9: MODULE CATALOGUE, ENTITLEMENTS AND INDUSTRY TEMPLATES

## 9.1 Catalogue

Each module has: ID, code, name, description, category, version, status, availability, dependencies, conflicts, permissions, routes, API endpoints, migrations, jobs, events, UI components, configuration, industry and plan compatibility, activation requirements, deactivation policy, data-retention policy.

## 9.2 Categories (extensible)

Core, Retail POS, Inventory, Pharmacy, Hardware, Restaurant & Café, Bar, Hotel, Wholesale, Logistics, Salon, Fuel, Suppliers/Purchasing, Accounting, CRM, HR, Reports, eTIMS, M-Pesa, Loyalty, Delivery, Online store, Advanced analytics.

## 9.3 Dependencies (machine-readable, validated)

Example: Pharmacy → Core, Retail POS, Inventory, Batch Tracking, Expiry Management. Restaurant → Core, POS, Inventory, Restaurant Operations, Kitchen Workflow. Hotel → Core, POS, Inventory, Hotel Operations, Accounting. Activation fails safely when dependencies are missing; deactivation is blocked when active dependents exist.

## 9.4 Distinct states (never conflate)

Installed (code and schema present in release) · Available (supported) · Entitled (tenant allowed) · Activated (enabled in tenant) · Configured (required settings complete) · Operational (dependencies and integration health verified).

## 9.5 Activation

Validate plan entitlement, dependencies, tenant compatibility, schema version, shop eligibility and configuration; apply migrations safely; register permissions, routes, jobs and event handlers; activate; audit; notify tenant.

## 9.6 Deactivation

Block new module actions, routes, API access and (where appropriate) background actions; hide menus and dashboard widgets; preserve data and audit; never delete module data; provide a reactivation path.

## 9.7 Entitlements

Grant, revoke, set limits and expiry, apply overrides, view plan vs custom origin, view conflicts and dependency status, review history. Every grant or revoke requires a reason.

## 9.8 Industry templates

Versioned presets containing: industry, modules, default permissions and roles, categories, units, tax settings and eTIMS defaults, chart-of-accounts suggestions, stock policies, shop configuration, workflow defaults, receipt settings, dashboard layout, module configuration. Template changes never silently alter live tenants. Applying a template to an existing tenant requires a tenant request or approved administrator action, a preview and diff, a backup where appropriate, approval and audit.

---

# PART 10: COMPLIANCE AND eTIMS MANAGEMENT

## 10.1 Compliance console

Tenant and shop compliance status; eTIMS configuration, device and integration status; certification; sandbox vs production; rejections; retry queues; pending, accepted and stalled submissions; specification versions; evidence; KRA correspondence.

## 10.2 Shop-level eTIMS monitoring

States: Not configured, Awaiting onboarding, Sandbox, Production, Suspended, Failed, Degraded, Unknown. Show shop identity, tenant, integration mode (platform-certified or self-integrated), driver (OSCU or VSCU), device status, last successful submission, queue depth, rejection count, error category, last health check, integration version, last configuration change. Invoice contents are not shown by default.

## 10.3 Submission monitoring

Aggregate submitted, accepted, rejected, pending, retry, failed, stalled; average latency; retry count; error classification; failure trend; queue backlog. Sanitised submission metadata is available to authorised compliance staff. Raw invoice payloads require break-glass.

## 10.4 BlePOS certification tracker

Status, KRA requirements, test cases, evidence, submitted documents, correspondence, sandbox results, rejected and pending tests, approved requirements, current specification version, responsible person, next action, deadline. Statuses: Not started, Preparation, Sandbox testing, Submitted, Rejected, Pending review, Approved, Interim status, Production-ready. Never claim certification that has not been obtained.

## 10.5 Data-protection requests

Types: access, correction, deletion, restriction, export, consent withdrawal, other. Track ID, requester, tenant, type, date received, verification status, deadline, assigned officer, status, response, completion, evidence, legal hold, with SLA timers. Requirements follow the Kenya Data Protection Act, 2019; legal interpretation is flagged "requires legal review".

## 10.6 Legal documents

Terms of service, privacy policy, data processing agreement, acceptable use policy, cookie policy, other notices. Versioning, effective dates, publication, tenant and user acceptance tracking, mandatory re-acceptance, history.

---

# PART 11: INFRASTRUCTURE AND HEALTH

## 11.1 Sections

Overview; application servers; database servers; tenant databases; queues; jobs; scheduler; cache; storage; backups; deployments; migrations; network/integrations; logs and monitoring.

## 11.2 Database servers and allocation

Per server: name, environment, engine, version, host metadata, tenant and database counts, capacity, connection limit and use, CPU, memory, disk, health, last check, alert status. Never expose passwords or connection secrets. Tenant allocation supports server selection, capacity checks, placement, migration status, distribution, maintenance and future relocation; relocation is a controlled, audited operation.

## 11.3 Migrations

Show central, tenant and module schema versions, current release, pending and failed migrations, tenants behind, run history. Actions: dry run, preview, run for one tenant, a selected batch, or all eligible tenants (with concurrency limits), retry, investigate, export report. Migrations are versioned, tested, idempotent where possible, backward-compatible where required, logged, and safe for large batches. Never run destructive migrations without explicit safeguards.

## 11.4 Queues, failed jobs, scheduler

Queue names, workers, throughput, pending, processing, failed, retry, oldest age, worker health, latency, tenant attribution. Failed jobs show sanitised information only (no secrets, no tenant business payloads, no unrestricted payload inspection); actions: retry, retry selected, retry after correction, mark investigated, resolve, escalate, delete failed-job metadata where policy allows. Scheduler: commands, frequency, last and next run, duration, status, missed run, failure, overlap, heartbeat; manual runs audited.

## 11.5 Cache and storage

Cache: provider, health, latency, memory, hit and miss rates, tenant and platform namespaces. Cache clearing is scoped; an operator can never clear another tenant's cache through a tenant-specific action. Storage: total, used, free, per-tenant usage (bytes only), growth, file counts, upload failures, orphaned-file detection, provider health, backup storage.

## 11.6 Deployments and feature flags

Deployments: version, build number, commit hash, date, environment, release notes, migration status, rollout status, rollback eligibility. Do not invent deployment controls if no deployment provider integration exists. Feature flags: platform-wide, module, tenant-specific, percentage rollout, allowlist, denylist, expiry, reason, owner, audit history. Flags never bypass permission or entitlement checks.

---

# PART 12: SECURITY CENTRE

## 12.1 Overview

Incidents, high-severity events, failed-login spikes, MFA failures, lockouts, suspicious sessions, privilege and role changes, break-glass access, API abuse, rate-limit violations, callback abuse, key rotation, encryption status, dependency vulnerabilities, platform-user security status.

## 12.2 Users, roles, sessions

Platform users: name, email, role, status, MFA status, last login, login failures, sessions, invitation status, creation date, last role change; actions as in 2.9. Roles: name, description, permissions, user count, built-in or custom, created, updated, last used; editor with permission groups, search, diff, conflict warnings, save, audit. Sessions: user, session ID, IP, device, browser, approximate location, login time, last activity, expiry, MFA method; revoke, revoke all, investigate.

## 12.3 Security event workflow

Event ID, category, severity, actor, target, tenant metadata, source, IP, timestamp, description, evidence metadata, status, assigned officer, notes, resolution. Statuses: New, Acknowledged, Investigating, Escalated, Resolved, Closed.

## 12.4 Key management

Track key ID, type, version, age, status, rotation schedule, last rotation, rotation and re-encryption progress, failures. Never display raw keys; prefer external KMS. Envelope encryption: per-tenant data keys wrapped by a master key held outside the database. Master key rotation requires step-up, two-person approval, backup, recovery procedure, progress tracking, failure handling and audit.

## 12.5 IP and abuse management

Allowlist, denylist, CIDR ranges, temporary and permanent blocks, expiry, reason, actor, security review. Tenant-specific rate-limit overrides are permission-controlled.

---

# PART 13: SUPPORT CENTRE

## 13.1 Dashboard and cases

Open tickets, priority, unassigned, overdue, escalated, waiting for customer, waiting for platform, resolved, with tenant, plan, modules and contact. Case fields: ID, tenant, shop, category, priority, status, requester, assigned agent, description, attachments, internal notes, customer replies, resolution, SLA, history.

## 13.2 Assist tools

Search tenants; view metadata; resend invitations; trigger password-reset emails; verify tenant status; check provisioning health; run permitted data-integrity checks (return pass/fail counts only); generate sanitised reports; review permitted tenant audit metadata; request break-glass.

## 13.3 No impersonation

Do not implement "Login as tenant" or "Impersonate user". Any impersonation-like proposal is a separate high-risk design requiring explicit human approval and is excluded from ordinary support.

## 13.4 Notes and announcements

Support notes: internal, timestamped, attributed, searchable, editable only per policy, audited, never visible to tenants unless classified tenant-visible. Announcements: create, draft, preview, schedule, publish, expire; target by tenant, industry, plan or module; priority; email and in-app delivery; read receipts; delivery status; failed-delivery retry.

---

# PART 14: PLATFORM AUDIT LOG

## 14.1 Coverage

Authentication, MFA, session creation and revocation; user creation, activation, deactivation; role and permission changes; tenant registration, provisioning, suspension, reactivation, archive, deletion request and approval; subscription, plan, discount, credit and refund changes; module grants and revocations; template changes; domain changes; database operations; migrations; backup and restore operations; security and key operations; break-glass requests, approvals and sessions; support actions; bulk actions; platform setting and feature-flag changes; compliance actions; exports; manual job operations; platform invoice fiscalisation events.

## 14.2 Event structure

Event ID, timestamp, actor ID and type, action, target type and ID, tenant ID, shop ID, source, IP, device, reason, before and after state where appropriate, correlation ID, request ID, result, approval reference. Avoid storing unnecessary personal data or any secrets.

## 14.3 Integrity

Append-only; tamper-evident via hash chaining (each record stores the hash of the previous); written through a database role with no UPDATE or DELETE privileges; independently verifiable; retained per policy. Provide chain verification, verification timestamp and result, broken-chain location, integrity alert, and a "Verify now" action. No edit or delete feature or API exists.

## 14.4 Search and export

Filters: actor, action, target, tenant, shop, date, severity, result, category, IP, correlation ID. Exports require permission and a reason, are audited, generated asynchronously for large sets, expire securely, are not publicly accessible, exclude secrets, and minimise personal data.

---

# PART 15: PLATFORM SETTINGS

General: name, logo, branding, support contacts, default language, time zone, currency, country, date and number formats. Communications: email and SMS providers, sender names and IDs, templates, retry, delivery logs, rate limits (credentials write-only). Platform payment settings (M-Pesa, bank, card, other gateways; encrypted, masked, write-only, rotatable, audited). **Platform eTIMS settings** (platform PIN, device records for platform invoices, kept separate from tenant and shop eTIMS credentials). Security: password, session, MFA policy, login rate limits, lockout, IP policy, allowed domains, alert thresholds. Data policy: retention for data, audit, backups, exports; deletion delay; legal hold; data residency; privacy policy; DPA settings. Feature flags. Maintenance mode: shows a message, permits configured administrator access, allows approved health checks, preserves emergency access, audited, reversible, never locks administrators out. Legal text editors.

---

# PART 16: PLATFORM DATABASE MODEL (CENTRAL)

Design, normalise and migrate these logical entities (add or merge as needed; define relationships, indexes, foreign keys, constraints, soft deletion where appropriate, retention):

- **Identity:** platform_users, platform_roles, platform_permissions, platform_role_user, platform_role_permissions, platform_sessions, platform_user_invitations, platform_mfa_methods, platform_recovery_codes, platform_login_events.
- **Tenants:** tenants, tenant_domains, tenant_contacts, tenant_tags, tenant_notes, tenant_status_events, tenant_database_assignments, tenant_database_metadata, tenant_lifecycle_events.
- **Provisioning:** provisioning_runs, provisioning_steps, provisioning_attempts, provisioning_failures, provisioning_locks.
- **Billing:** plans, plan_versions, plan_limits, plan_modules, subscriptions, subscription_events, subscription_overrides, platform_invoices, platform_invoice_lines, platform_payments, payment_allocations, platform_credit_notes, platform_refunds, dunning_schedules, dunning_events, platform_invoice_etims_documents.
- **Modules:** modules, module_versions, module_dependencies, module_conflicts, module_permissions, tenant_module_entitlements, tenant_module_activations, module_activation_events.
- **Templates:** industry_templates, template_versions, template_modules, template_settings, template_roles, template_categories, template_application_runs.
- **Monitoring:** health_checks, health_check_results, metrics_snapshots, tenant_usage_snapshots (daily aggregates only), capacity_snapshots, queue_metrics, scheduler_metrics, database_metrics, deployment_records, migration_runs, migration_tenant_results.
- **Security:** platform_security_events, platform_audit_log, platform_audit_integrity, approval_requests, break_glass_requests, break_glass_approvals, break_glass_sessions, key_versions, key_rotation_runs, ip_rules, security_incidents.
- **Support:** support_cases, support_case_messages, support_case_notes, support_case_attachments, support_case_assignments, support_case_events.
- **Compliance:** etims_platform_status, tenant_compliance_metadata, shop_compliance_metadata, compliance_checklists, compliance_evidence, compliance_events, data_protection_requests, legal_documents, legal_document_versions, legal_acceptances.
- **Communications:** announcements, announcement_audiences, announcement_deliveries, announcement_read_receipts, platform_notifications, communication_templates, communication_delivery_logs.
- **Configuration:** platform_settings, platform_integrations, platform_feature_flags, feature_flag_rules, backup_runs, restore_tests, maintenance_windows.

---

# PART 17: BREAK-GLASS TENANT ACCESS

## 17.1 Principle

Platform personnel never have standing access to tenant business records. Break-glass is an exceptional, scoped, time-limited, read-only, fully audited procedure.

## 17.2 Workflow

1 Support agent files a request (tenant, shops, data category, reason, duration). 2 Tenant owner is notified and approves or denies. 3 If the owner is unavailable during a qualifying declared incident, a two-person emergency approval applies (including the Security Officer). 4 Access is granted as a time-boxed, scoped, read-only session. 5 Every record viewed is logged with identifiers. 6 The session expires automatically; the tenant can revoke it at any time. 7 Post-access review by the Security Officer.

## 17.3 Scope and restrictions

Scope states tenant, shops, data categories, read-only, allowed records where feasible, purpose, duration (default and maximum 60 minutes), approvers. Break-glass NEVER permits: payments, refunds, sales, inventory or user or settings changes, deletes, exports beyond approved scope, privilege escalation, access to another tenant, unrestricted database browsing. Fixes are made through guided tenant-side actions or approved, audited maintenance scripts.

## 17.4 Tenant visibility

Each tenant has a "Platform Access Log" page: platform user, role, purpose, approval, scope, start and end time, records accessed, revoked or expired, review outcome.

## 17.5 Tests

Denied request; expired request; wrong tenant; wrong shop; scope violation; time expiry; tenant revocation; self-approval rejected; unauthorised approver rejected; session reuse blocked; read-only enforcement; audit completeness.

---

# PART 18: TENANT-SIDE DASHBOARD REQUIREMENTS

## 18.1 General rules

Every tenant dashboard respects tenant boundary, shop access, role permissions, module entitlement and activation, and the active shop; uses real backend data; computes KPIs server-side; offers date filters and drill-down; has loading, empty and error states; shows a timestamp; handles offline state where applicable; handles large datasets efficiently; and never queries data from the client directly. Shop switching updates the active shop, revalidates authorisation, refreshes widgets, cancels stale requests, prevents previous-shop data from appearing, and resets shop-specific filters.

## 18.2 Widget permission rules

Sales widgets need sales/report permissions; profit widgets need cost and profit permissions; cash widgets need cash-management permissions; M-Pesa widgets need payment-monitoring permissions; HR widgets need HR permissions; pharmacy, hotel and other industry widgets need their module permissions; eTIMS widgets need compliance permissions; accounting widgets need finance permissions. Hiding a widget is not enough: the API must deny the underlying data.

---

# PART 19: TENANT OWNER / BUSINESS MANAGER DASHBOARD

- **KPI strip (where permitted):** gross sales, net sales, sales excluding tax, tax, transaction count, average basket, gross margin, gross profit, net profit (only where accounting supports it), sales by cash, M-Pesa, card, credit and other, refunds, voids, discounts, expenses, cash position, receivables, payables. Gross sales, net sales, gross profit, operating profit and cash position are clearly distinguished.
- **Shop performance:** ranking, sales, profit, margin, expenses, targets, transactions, average basket, payment split, refunds, stock loss, cash variance; comparison and drill-down.
- **Sales analytics:** hourly, daily, weekly, monthly, yearly; previous period; targets; growth; customer trends.
- **Product analytics:** top and bottom products and categories; high and low margin; slow and non-moving; stock-outs; reorder candidates; dead stock; profitability.
- **Finance and liquidity:** cash position; receivables and payables with ageing; expenses; cash flow; forecast (assumptions stated); credit limits; overdue customers.
- **Payments health:** M-Pesa by shop, success rate, pending, failed, unreconciled, reconciliation backlog, settlement variance, method comparison.
- **Tax and eTIMS:** VAT collected, input and output; tax liability and due dates; tax payments recorded; eTIMS accepted, pending and rejected documents; unfiscalised transactions (red when above zero); expenses lacking valid eTIMS invoices; compliance issues by shop.
- **Control signals:** high refund and void rates, unusual discounts, cash and register variance, stock adjustments, repeated manager overrides, reconciliation issues, with explanations and drill-down (no automatic accusations).
- **Approvals inbox:** refunds, purchase orders, expenses, stock adjustments and transfers, price changes, discounts, supplier approvals, leave, credit, register exceptions.
- **Subscription banner:** plan, renewal date, usage versus limits.

---

# PART 20: SHOP MANAGER DASHBOARD

Scoped to assigned shops unless tenant-wide permission exists. **Daily sales:** sales today and by hour, transactions, average basket, target and achievement, payment split, returns, voids, discounts. **Registers:** open and closed, cashier, shift, opening float, expected and actual cash, variance, held sales. **Inventory:** low stock, stock-outs, negative stock, incoming stock and transfers, adjustments, count tasks, expiring and expired stock, reorder candidates. **Staff:** on duty, attendance, late and absent, sales per cashier, targets, shift assignments (authorised employee data only). **Expenses:** today, pending, approved, rejected, cash outflows. **Payments and fiscal health:** M-Pesa configuration and callback status, pending and failed payments, reconciliation, settlement; **eTIMS device status, queue depth, last accepted time**. **Tasks:** close registers, reconcile M-Pesa, receive transfers, approve items, complete counts, resolve exceptions.

---

# PART 21: CASHIER DASHBOARD

**Home:** assigned shop and register, shift status, opening float, open-shift action, previous shift summary (where permitted), held sales, recent transactions, online/offline state, sync queue, fiscalisation status, payment status. **Restrictions:** no business profit, no cost price unless permitted, no owner analytics, no other cashiers' totals unless permitted, no billing, no platform administration, no other shops, minimal customer detail. **Shift workflow:** open, float, cash sales, cash movements, drops, held sales, close, cash count, variance, approval, audit. **Offline view:** offline/online/syncing/sync failure, pending transactions, last successful sync, local data status, register status. Offline sales are never silently lost.

---

# PART 22: ACCOUNTANT / FINANCE MANAGER DASHBOARD

Overview: cash, bank, M-Pesa, receivables, payables, revenue, expenses, tax, profit, cash flow. Accounting integrity: trial balance, ledger balance, unposted and failed postings, reversed journals, period status, reconciliation status, subledger differences, **eTIMS-versus-ledger differences**. Reconciliation: bank, M-Pesa, register, cash, gateway; unmatched and duplicate payments; settlement differences. Tax: VAT position, input and output, tax due and paid, deadlines, reports, payment records. Receivables: outstanding, current, 30, 60, 90 and over 90 days, credit, limits, collection status. Payables: supplier balances, upcoming and overdue payments, supplier credit, purchase obligations. Financial approvals: expenses, payments, refunds, credit notes, journal reversals, purchases, supplier payments.

---

# PART 23: INVENTORY AND WAREHOUSE DASHBOARD

KPIs: total stock value, by shop, warehouse and category; movement; turnover; availability; stock-outs; low stock; negative-stock exceptions. Operations: incoming and outgoing goods, transfers (pending), receipts, adjustments, returns, counts, variances. Valuation: the configured costing method is shown explicitly; cost and valuation data are permission-gated. Replenishment: reorder suggestions, minimum and maximum, reorder point, lead time, supplier, suggested quantity, pending purchase orders, incoming stock. Expiry and batch (where enabled): batch, lot, expiry, near-expiry, expired, recall, batch quantity, shop location; configurable alert thresholds. **eTIMS stock-report sync status** where applicable.

---

# PART 24: PROCUREMENT DASHBOARD

Overview: requisitions, draft, awaiting approval, approved, sent, partially received, fully received, overdue, cancelled purchase orders. Supplier analytics: balances, performance, delivery time, price trends, quality issues, outstanding orders. Controls: requests, approval, purchase orders, receiving, invoice matching, price and quantity variance, supplier returns. Three-way match of purchase order, goods receipt and supplier invoice, with exceptions identified. **Incoming eTIMS purchase invoices:** validation, acceptance, rejection, matching, reconciliation (where the integration is available).

---

# PART 25: HR MANAGER DASHBOARD

Headcount, employees by shop, active and inactive; attendance today (present, absent, late, on leave, on shift); upcoming shifts, shift gaps, overtime, shift conflicts, leave calendar, attendance exceptions; staff performance where permitted (sales, targets, commission, attendance, training, trends). Salary, personal data, disciplinary and other sensitive HR information are restricted by specific permissions and access is audited.

---

# PART 26: AUDITOR DASHBOARD

Strictly read-only: no write controls, no approvals, no configuration changes, no mutation endpoints. Overview: voids, refunds, discounts, overrides, stock adjustments, register and cash variance, reversed journals, deleted and reversed entries, user activity, permission changes. Tools: audit search, shop access matrix, role and permission history, approval history, transaction audit, exception reports, permissioned export.

---

# PART 27: INDUSTRY-SPECIFIC DASHBOARDS

Each is accessible only when the tenant is entitled to and has activated the module, and only after that module's real backend exists.

- **Pharmacy:** sales, prescription and dispensing activity, batch inventory, expiry, near-expiry and expired products, recall alerts, availability, controlled-item records where legally applicable, prescription queue, pharmacist approvals, purchases, valuation, compliance. Sensitive dispensing and customer data are permission-protected.
- **Restaurant:** orders, tables, open tables, reservations, kitchen queue, preparation time, delayed and cancelled orders, food cost, ingredient availability, low stock, waste, sales by category, staff performance.
- **Bar:** open tabs, tab balances and ageing, bar sales, beverage stock and variance, bottle tracking where implemented, waste, promotions, shift sales, cash variance.
- **Hotel:** occupancy, available and occupied rooms, arrivals, departures, reservations, cancellations, ADR, RevPAR, revenue, folios, outstanding balances, housekeeping, maintenance, room status, booking channel.
- **Wholesale:** wholesale sales, orders, bulk pricing, customer credit and balances, delivery, stock allocation, warehouse availability, margins, large orders, price tiers.
- **Logistics:** deliveries, dispatch, routes, status, drivers, vehicles, proof of delivery, failed delivery, delivery cost, outstanding POD, route performance.
- **Salon:** appointments, today's schedule, bookings, walk-ins, no-shows, staff utilisation, service revenue, product sales, commissions, retention.
- **Fuel:** tank levels and capacity, deliveries, pump meters, dip measurements, pump variance, sales volume, revenue, fuel loss, reconciliation, valuation. Regulatory and eTIMS fuel-station requirements must be verified before this module is represented as compliant.

---

# PART 28: DASHBOARD ENGINE AND WIDGET ARCHITECTURE

Build one reusable framework. Widget types: KPI cards, charts, tables, alerts, activity feeds, task lists, comparisons, progress and targets, with drill-down, permission and module requirements, shop scope, date range, refresh and export where permitted. Each widget definition declares: ID, name, data source, query or service, permission, module dependency, shop scope, tenant scope, cache rule, date range, refresh interval, drill-down, empty state, error state. Role-based default layouts; users may rearrange, hide, reset and save layouts (shop-specific or tenant-wide), and customisation never grants access to restricted data.

Performance: no N+1 queries, aggregate queries, appropriate indexes, caching, paginated detail tables, asynchronous processing for heavy reports. Initial targets, to be measured and not merely claimed: lightweight dashboard API p95 of 500 ms or less under a defined test load; first usable render within 2 seconds under defined conditions; heavy analytics may load asynchronously; drill-down never fetches whole datasets.

---

# PART 29: PLATFORM NOTIFICATIONS AND ALERTS

Channels: in-app, email, optional SMS, webhook where configured. Categories: provisioning, billing, security, compliance, infrastructure, backup, capacity, support, announcements, approvals, tenant lifecycle. Alert rules: threshold, severity, condition, duration, frequency, recipient, escalation, acknowledgement, resolution; avoid alert flooding. Escalate unresolved alerts by severity, age, service, responsible team and incident policy.

---

# PART 30: DATA PROTECTION, PRIVACY AND RETENTION

Follow data minimisation, purpose limitation, least privilege, access logging, retention, secure deletion, encryption and tenant isolation. Mask email, phone, business PIN, personal identifiers and payment metadata where appropriate; exports minimise personal information. Define retention for platform audit, security events, sessions, login events, support records, billing, tenant metadata, backups, provisioning logs, metrics, communications and break-glass records, considering legal and business obligations (flag items needing legal or tax-adviser review). Data exports are authorised, scoped, audited, encrypted where appropriate, expiring, never public, revocable. Deletion checks legal hold, retention, billing and audit obligations, backup policy, tenant dependencies, requires approvals, records evidence and never risks other tenants.

---

# PART 31: BACKUP, RESTORE AND DISASTER RECOVERY

Monitor backups for the central database, tenant databases, files, configuration, encryption-key recovery metadata and infrastructure configuration. Dashboard: type, source, tenant, database, start, duration, size, status, destination, encryption, retention, integrity check, error. **Tenant restore workflow:** request → identify tenant → choose restore point → confirm scope → validate backup → approval → restore to an isolated environment → verify → review → approve production recovery → execute → confirm → audit. Safeguards: two-person approval, isolated test restore, backup verification, restore logs, tenant identity verification, shop and domain verification, database consistency check, rollback strategy. A live tenant database is never overwritten through a casual admin action. Targets to document and verify: RPO of 15 minutes or less, RTO of 4 hours or less for the platform, tenant-level restore within 1 hour for a typical tenant.

---

# PART 32: PLATFORM REPORTS AND ANALYTICS

Reports: tenant growth, activation, retention, churn, revenue, collections, subscriptions, plan distribution, module adoption, industry distribution, shop and user growth, payment-integration adoption, eTIMS adoption, uptime, queue performance, database and storage growth, security events, support volume and resolution, compliance status. Controls: date range, filters, comparison, drill-down, export, permission, saved and scheduled reports, delivery. Every report defines source, calculation, time zone, currency, filters, inclusion and exclusion rules, and has automated tests.

---

# PART 33: PLATFORM API AND BACKEND ARCHITECTURE

Separate services for: platform authentication, users, roles, tenant management, provisioning, lifecycle, subscriptions, billing, module catalogue, entitlements, templates, health monitoring, security monitoring, audit, support, compliance, notifications, analytics, backup, restore. Every service enforces authentication, permission, tenant scope, shop scope where relevant, action restrictions, entitlement, state validation and audit. API security: input validation, rate limiting, CSRF where applicable, request size limits, secure serialisation, error handling, idempotency, correlation IDs, logging without secrets. Sensitive actions require permission, step-up, reason, approval, idempotency and audit.

---

# PART 34: CONSOLE UX REQUIREMENTS

Minimalist, clean, responsive, fast, accessible, consistent, information-dense. Tables: pagination, search, filtering, sorting, column visibility, saved views, bulk selection and actions, empty, loading and error states. Forms: validation, inline and server errors, clear labels, required indicators, save feedback, cancel, confirmation for dangerous actions, drafts where useful. Dangerous actions use a dedicated flow: action summary, target, impact, reason, step-up, typed confirmation where appropriate, approval, audit. Accessibility: keyboard navigation, screen-reader support, focus states, labels, contrast, accessible charts, reduced motion, responsive layouts.

---

# PART 35: ACCEPTANCE TESTS

- **Authentication:** tenant user cannot enter the console and platform user cannot enter tenant areas; sessions cannot cross domains; MFA required and unbypassable; recovery codes one-time; invalid recovery codes rejected; session and idle expiry; logout revokes; secure password reset; rate limits and lockouts; deactivation blocks access.
- **Roles and permissions:** for every role, allowed actions work and forbidden ones fail via UI, direct URL and API; hidden menus are not treated as security; changes take effect immediately and are audited; custom roles cannot escalate; Super Administrator restrictions hold (including a database-level test that audit rows cannot be updated or deleted).
- **Tenant isolation:** Tenant A cannot read, query, export, or reach Tenant B's data, files, cache, jobs, payments, reports or shops; the console cannot read tenant records without break-glass.
- **Lifecycle:** registration, provisioning success and failure, retry, duplicate protection, partial-failure recovery, invitation, activation, trial, subscription, grace, suspension, reactivation, archive, deletion request and approval, cancellation, retention.
- **Provisioning:** inject failures at every step and verify correct state and error, safe retry, no duplicates, tenant stays inactive, sanitised logs, database and domain integrity.
- **Billing:** plan creation and versioning, subscriptions, upgrade, downgrade, proration, trial, renewal, cancellation, payment, failed payment and retry, refund, credit note, dunning, grace, suspension, reactivation, **platform-invoice eTIMS fiscalisation and credit notes**; verify calculations against independent reference calculations, including MRR and ARR.
- **Modules:** entitlement, activation, dependencies and missing dependencies, deactivation with data preserved, plan limits, overrides, templates, migrations, unauthorised routes and APIs.
- **Approvals:** every action in the matrix of 3.5 enforces step-up, two-person and delay rules against direct API calls; same-user approval rejected; delayed actions are cancellable.
- **Dashboards:** every KPI compared with an independent reference query on seeded data; permissions, shop and tenant scope, date filters, time zones, currency, empty and failure states, cache invalidation, stale responses and performance.
- **Security:** CSRF, XSS, SQL injection, IDOR, SSRF where relevant, session fixation, authentication and authorisation bypass, rate limits, brute force, tenant and shop leakage, secret exposure, file and export access, webhook security.
- **Audit:** every sensitive action is audited with correct actor, target, tenant, reason, correlation ID and before/after values; no secrets in payloads; chain verified; tampering detected; no modification through the API.
- **Break-glass:** per 17.5.
- **Infrastructure:** health-check failure and timeout, missing source (shows Not configured), queue backlog, failed job, scheduler miss, database or storage unavailable, backup failure, restore test, migration failure.
- **Performance:** large tenant list, large audit dataset, many tenants and shops, high job volume, dashboard aggregation, concurrent administrators; document measured results.

---

# PART 36: IMPLEMENTATION ORDER

Follow the tiers in 0.7 and the mapping in 0.8. Within the work:

- **A1 Architecture and security foundation:** control plane architecture, platform guard and user model, authentication, MFA, sessions, permissions, audit infrastructure, security tests. Do not build dashboard KPIs before this is VERIFIED.
- **A2 Platform users and roles:** staff, invitations, roles, permissions, sessions, security events, role editor, tests.
- **A3 Tenant registration and management:** list, wizard, detail, identity, contacts, domains, status, lifecycle, tests.
- **A4 Provisioning engine:** workflow, step tracking, retries, idempotency, database allocation, migrations, initial configuration, shops, owner invitation, health verification, failure tests.
- **A5 Subscription and billing:** plans, versions, subscriptions, billing, invoices (with eTIMS), payments, collections, dunning, credit notes, reports, tests.
- **A6 Module catalogue and templates:** metadata, dependencies, entitlements, activation, deactivation, templates, plan validation, module migration, tests.
- **A7 Infrastructure monitoring:** health checks, databases, queues, scheduler, storage, cache, backup, restore, deployment, migrations.
- **A8 Security centre:** events, sessions, IP rules, accounts, key management, incidents, alerts, tests.
- **A9 Compliance:** eTIMS console, certification tracker, shop compliance, data-protection requests, legal documents, tests.
- **A10 Support centre:** cases, tenant lookup, assist tools, notes, announcements, notifications, tests.
- **A11 Platform analytics:** growth, lifecycle, subscriptions, revenue, module adoption, industry analytics, retention, reports.
- **A12 Platform dashboard:** integrate completed widgets, each with its real source; never a standalone visual exercise.
- **A13 Tenant dashboards:** delivered with their domain modules, not postponed; each module delivers data, workflow, permissions, dashboard, reports and tests.
- **A14 Hardening and release readiness:** full security, isolation and authorisation audits, performance, backup and restore testing, disaster-recovery documentation, monitoring validation, deployment documentation, release checklist.

---

# PART 37: DOCUMENTATION

Maintain under `docs/`: PLATFORM_ARCHITECTURE, PLATFORM_SECURITY, PLATFORM_AUTHENTICATION, PLATFORM_ROLES_PERMISSIONS, PLATFORM_TENANT_LIFECYCLE, PLATFORM_PROVISIONING, PLATFORM_BILLING, PLATFORM_MODULES_ENTITLEMENTS, PLATFORM_INDUSTRY_TEMPLATES, PLATFORM_DASHBOARDS, PLATFORM_MONITORING, PLATFORM_COMPLIANCE, PLATFORM_ETIMS, PLATFORM_MPESA, PLATFORM_SUPPORT, PLATFORM_AUDIT, PLATFORM_BACKUPS_RECOVERY, PLATFORM_DATA_PROTECTION, PLATFORM_API, PLATFORM_TESTING, PLATFORM_DEPLOYMENT, PLATFORM_INCIDENT_RESPONSE, PLATFORM_PROGRESS (all `.md`). Documentation describes only what is actually implemented; unimplemented functionality is never described as operational.

---

# PART 38: PROGRESS TRACKING

Keep a master progress file with one row per feature and these fields: Feature; Phase; Status (VERIFIED, IMPLEMENTED-UNVERIFIED, PARTIAL, BLOCKED, NOT STARTED, FAILED); Backend (files/services); Frontend (screens/components); Database (tables/migrations); Permissions; Tests and results; Dependencies; Risks; Next action. Update after every phase. A phase is never marked VERIFIED because some of its screens are complete.

---

# PART 39: FINAL ACCEPTANCE CRITERIA

The annex is complete only when all of the following are true and evidenced:

1. The Platform Console is a distinct, secure administrative area with authentication independent of tenants, working mandatory MFA, manageable platform users, complete roles and permissions, and server-side permission enforcement.
2. Tenant registration, provisioning, provisioning recovery and the controlled lifecycle all work; suspension preserves data; reactivation works; archive and deletion safeguards work.
3. Plans, versions, subscriptions, platform billing, invoices (fiscalised through eTIMS under the platform PIN, subject to certification status), payment reconciliation and dunning work.
4. The module catalogue, entitlements, safe activation and deactivation, and versioned industry templates work.
5. Infrastructure, queue, scheduler, database and backup monitoring use real sources; restore workflows have been tested.
6. The security centre works; the platform audit log is append-only and tamper-evident; break-glass is controlled, scoped and time-limited; support tools work without impersonation; the approval matrix in 3.5 is enforced.
7. Compliance workflows work; eTIMS and M-Pesa monitoring use real data sources and show true connection states (sandbox, production, unavailable).
8. The platform dashboard shows accurate data with no fabricated figures or health statuses; reports are accurate; notifications and data-protection workflows work.
9. The tenant owner, shop manager, cashier, accountant, inventory, procurement, HR, auditor and industry dashboards work with enforced shop scoping and permissions, each delivered with its underlying module.
10. Performance targets are measured; security, tenant-isolation, authorisation, dashboard-KPI, and backup/restore tests pass; documentation is complete and accurate; progress tracking is current.

---

# PART 40: FINAL INSTRUCTIONS TO THE DEVELOPMENT AGENT

Do not treat this annex as a request for a dashboard mockup. Do not skip backend implementation, substitute static cards for real functionality, or hardcode KPIs. Do not expose tenant business data to platform personnel by default. Do not bypass platform authentication, MFA, authorisation, tenant isolation, shop scope, module entitlements, audit logging or approval workflows. Do not create features that look complete but cannot perform their operation. Do not weaken security to speed development. Do not delete tenant data when disabling modules, suspending or archiving tenants, or changing subscriptions or plans. Do not implement tenant impersonation. Do not expose plaintext credentials. Do not silently modify live tenant settings when templates or platform policies change. Do not mark anything VERIFIED without tests and evidence.

Architectural commitments to keep explicit:
1. The Super Administrator owns platform administration, not unrestricted access to customer business records.
2. Platform billing is separate from tenant accounting; BlePOS subscription revenue never mixes with a tenant's sales, stock, M-Pesa collections or ledgers.
3. Shop-level integrations (M-Pesa, eTIMS, registers, permissions, settings) are managed and scoped per shop.
4. Dashboards are built alongside the modules whose data they show; a Pharmacy dashboard cannot be VERIFIED before pharmacy inventory, batches and expiry workflows exist.
5. External services are real: eTIMS, Daraja, email, SMS, monitoring, backups and payment integrations must show whether they are connected, tested, sandbox, production or unavailable, and must never be faked.

For each phase: inspect dependencies; write a brief plan; implement the database layer, backend services, authorisation, audit, UI, validation and error handling; implement automated tests; run them; fix failures; update documentation and progress; report in detail; proceed only when the phase is stable or its blockers are clearly documented.