# BlePOS Application Review

**Review date:** 25 November 2026  
**Repository checkpoint:** `main`, commit `08d36da`  
**Scope:** Static review of Laravel application code, routes, models/services, migrations, test inventory, and deployment/security documentation. No live penetration test, provider configuration audit, or production environment inspection was performed. This is a code review, not a regulatory or compliance certification.

## Executive summary

BlePOS has a broad Laravel foundation: separate platform and tenant guards, RBAC, tenant/shop context middleware, POS and stock workflows, a platform console, billing/support screens, and a substantial feature-test suite. The implementation is not production-ready for real payments, regulated pharmacy dispensing, or disaster recovery yet.

The most urgent issues are:

1. **POS sales trust price and tender values supplied by the browser.** An authenticated cashier can submit a lower item price and a payment below the actual product value; the server still records the sale as completed and reduces stock.
2. **Tenant subscription billing treats payment initiation or a user-entered reference as proof of payment.** The application can activate a paid subscription before Safaricom or another provider confirms settlement.
3. **The M-Pesa callback endpoint does not process or verify payment results.** It acknowledges the callback token and returns, so there is no authoritative settlement/reconciliation path.
4. **Pharmacy safety code is not connected to the POS sale flow.** Expiry/batch checks and dispensing controls exist as a service, but no pharmacy routes/UI call them; generic checkout can bypass those checks.
5. **Backup and restore operations are simulations.** The backup service records fabricated file metadata and checksums; it does not write or restore a backup artifact.

Treat these as release blockers before handling live transactions or patient-related data.

## Security and data-integrity findings

| Priority | Finding | Evidence and impact |
|---|---|---|
| **Critical** | **POS price and payment totals are client-controlled.** | [`PosController`](../app/Http/Controllers/Tenant/PosController.php#L74-L113) accepts `items.*.unit_price` from the request (including zero) and accepts tender amounts without tying them to the computed total. [`SaleService`](../app/Services/SaleService.php#L78-L84) uses that submitted price, then records tender values and marks the sale completed without requiring `paid_total >= grand_total` ([lines 141–190](../app/Services/SaleService.php#L141-L190)). An authenticated cashier can create underpriced or effectively unpaid completed sales while stock is deducted. **Fix first:** derive prices and tax server-side; validate discounts against permission/approval; calculate payments on the server; enforce exact settlement or explicit credit; add idempotency and concurrency protection. |
| **Critical** | **Tenant billing can grant paid status without verified settlement.** | [`TenantSubscriptionController`](../app/Http/Controllers/Tenant/TenantSubscriptionController.php#L67-L73) accepts `mpesa_paybill`, `card`, and `bank_transfer` plus a user-provided reference. For those methods it proceeds directly to `recordPayment`; for STK it calls the gateway and treats an initiation response as successful payment before recording the invoice as paid and activating the subscription ([lines 107–169](../app/Http/Controllers/Tenant/TenantSubscriptionController.php#L107-L169)). [`PlatformBillingService::recordPayment`](../app/Services/PlatformBillingService.php#L89-L145) records the payment as `completed` without external confirmation. **Fix:** keep every attempted payment `pending`; only transition after a verified provider callback or a controlled platform reconciliation action with evidence. Do not allow tenant-entered references to complete payments. |
| **High** | **M-Pesa callbacks are acknowledged but not reconciled.** | [`MpesaCallbackController`](../app/Http/Controllers/MpesaCallbackController.php#L16-L62) resolves an opaque URL token, logs callback context, and returns `Accepted`; it does not parse/validate result code, amount, receipt, checkout ID, invoice/sale, or duplicate callbacks, and does not update a payment record. [`MpesaStkPushService`](../app/Services/MpesaStkPushService.php#L72-L93) defines success from the STK request response code, which only represents the request response in this code path. **Fix:** persist a pending transaction before initiation; validate callback shape and credential context; verify amount, account reference, checkout ID, and result; use unique provider receipt/checkout IDs for idempotency; reconcile unmatched callbacks and expose them to operations. |
| **High** | **Pharmacy controls are bypassable through normal checkout.** | [`PharmacyService`](../app/Services/PharmacyService.php#L65-L129) contains batch/expiry and controlled-substance checks, but it is not invoked by [`SaleService`](../app/Services/SaleService.php#L78-L127) or `PosController`. `routes/web.php` has no pharmacy/prescription/dispensing routes, and there are no pharmacy feature tests beyond the demo seeder. The service also does not itself require a prescription for prescription-only products, and `logDispensing` can create a log even when the selected batch has insufficient quantity ([lines 134–170](../app/Services/PharmacyService.php#L134-L170)). **Fix:** integrate all pharmacy policy into the transactional sale/dispense service, enforce prescription requirements and batch depletion, then test authorization, expiry, quarantine, and concurrent sales before enabling the module for real pharmacies. |
| **High** | **Tenant global scopes fail open when tenant context is absent.** | [`TenantScope`](../app/Models/Scopes/TenantScope.php#L10-L17) adds a tenant predicate only when `TenantContext::hasTenant()` is true; otherwise the query is unscoped. [`ResolveTenantContext`](../app/Http/Middleware/ResolveTenantContext.php#L44-L57) relies on a terminable middleware hook to clear static context and leaves its `finally` block empty. Normal tenant HTTP routes have context middleware, but a command, job, listener, or future route can accidentally query every tenant’s records. **Fix:** fail closed for tenant-owned model access unless a trusted explicit all-tenant context is used; wrap context lifetime in `try/finally`; require explicit tenant context in jobs and add tests for absent/stale context and long-lived workers. |
| **High** | **Privileged tenant accounts have no MFA implementation.** | The tenant login verifies email/password and logs the user in directly in [`TenantAuthController`](../app/Http/Controllers/Tenant/Auth/TenantAuthController.php#L28-L69). Tenant `User` has no TOTP/recovery state. This conflicts with [`SECURITY.md`](SECURITY.md), which says privileged tenant administrators require MFA. A stolen owner or manager password is therefore sufficient for administrative access. **Fix:** add tenant MFA enrollment/challenge/recovery and step-up for sensitive actions, or correct the security specification and provide compensating controls before production. |
| **High** | **Restore approval does not restore data.** | [`InfrastructureService::triggerBackup`](../app/Services/InfrastructureService.php#L261-L284) creates a synthetic filename, random size, and a checksum of metadata; no backup file is generated. [`approveAndExecuteRestore`](../app/Services/InfrastructureService.php#L310-L328) changes request status to `production_completed` without importing data. The interface can report success when there is no recoverable artifact. **Fix:** implement encrypted off-host snapshots, checksums of actual bytes, retention/lifecycle rules, isolated restore execution, post-restore verification, and periodic restore drills. Until then label these actions as simulations and remove production-success wording. |
| **Medium** | **Platform/session hardening depends on deployment overrides.** | [`.env.example`](../.env.example#L1-L54) has `APP_DEBUG=true`, blank secure-cookie and force-HTTPS values, local filesystem, database queue/cache defaults, and SQLite. These are development defaults, but if copied into production unchanged they expose debug details and use non-durable/local infrastructure. `docs/DEPLOYMENT.md` describes the production overrides, but there is no checked-in production deployment manifest or environment assertion. **Fix:** fail deployment/boot checks when `APP_DEBUG` is enabled, `APP_KEY` is missing, HTTPS cookies are disabled, or production is using SQLite/local storage unintentionally. |
| **Medium** | **M-Pesa STK initiation is not linked to a sale and is not rate-limited.** | [`PosController::initiateMpesaStkPush`](../app/Http/Controllers/Tenant/PosController.php#L130-L170) accepts a caller-supplied amount and account reference and sends the STK request; it does not create a pending sale/payment record or apply a route throttle. A permitted cashier can repeatedly trigger requests for arbitrary amounts/phones. **Fix:** initiate only against a server-created pending checkout, derive amount/reference, rate-limit per user/shop/phone, and prevent duplicate requests. |

## Partially implemented or not production-ready

- **M-Pesa:** encrypted credentials and STK request construction exist; production settlement, callback verification, idempotency, reversals, and reconciliation do not. The project documentation correctly describes the current integration as sandbox/placeholder in several places.
- **KRA eTIMS:** the compliance console is a checklist/placeholder. The repository explicitly blocks production transmission pending certification. No live fiscal document submission or acknowledgement path is present.
- **Pharmacy:** tenant demo data, batch/prescription/dispensing tables, and a policy service exist. There is no pharmacy UI/API workflow wired into POS, no clinical audit screen, and no end-to-end safety tests.
- **Industry modules:** the module catalogue advertises pharmacy, hotel, wholesale, salon, and fuel workflows, while the current operational model/controller set primarily covers generic POS, inventory, accounting, pharmacy policy logic, and restaurant tables. No customer, supplier, purchase-order, room/reservation, or fuel-operation controllers/models were found in the application tree. Treat module catalogue entries as roadmap/entitlement descriptions until each workflow has an implemented route, UI, authorization, tests, and operational docs.
- **Public API:** documentation describes a platform REST API, but no `routes/api.php` or versioned tenant API routes were found. The M-Pesa callback is the only `/api`-style route in `routes/web.php`. API authentication, versioning, throttling, request signing, and idempotency are therefore not established.
- **Notifications:** the in-app database inbox is implemented; email/SMS delivery and operational retry/queue behavior are not configured by default (`MAIL_MAILER=log`, queue driver `database` in `.env.example`).
- **Production database/storage:** development uses SQLite and local filesystem defaults. The docs list MySQL/PostgreSQL and S3-compatible storage, but deployment portability, SQL differences, concurrent POS load, persistent uploads, and failover have not been validated on a production-like stack.

## Testing review

### What is covered

The suite has broad Laravel feature tests for tenant/shop isolation, POS and inventory, accounting, RBAC, platform authentication/MFA, platform approvals, billing, support, security events, platform audit integrity, backups-as-currently-simulated, and the recent communication and pharmacy demo paths. The current tree contains **51 feature test files and 2 unit test files**. A static scan found **226 named `test_*` methods**; PHPUnit can report more cases when data providers expand.

### Gaps and confidence limits

- No browser-level Playwright/Cypress acceptance suite is configured. Blade rendering, keyboard flows, scanner behavior, responsive layout, and real browser JavaScript interactions are not covered end-to-end.
- No coverage report is configured in `phpunit.xml`; there is no measured line/branch coverage baseline.
- No pharmacy workflow feature tests cover prescription enforcement, controlled drugs, expiry/quarantine at checkout, batch depletion, or audit history.
- POS tests do not currently establish that server-derived price/tax and paid-total invariants cannot be bypassed; add adversarial request tests for zero/discounted prices, underpayment, cross-shop registers, duplicate submission, and concurrent stock depletion.
- Payment tests use mocked HTTP. They do not verify real Daraja callback signatures/payloads, duplicate/out-of-order callbacks, gateway timeout/retry behavior, unmatched receipts, or live payment reconciliation.
- Tests run against in-memory SQLite (`phpunit.xml`). MySQL/PostgreSQL behavior, query isolation under concurrent requests, and production audit-trigger equivalents are not established.
- Restore tests exercise current service behavior, but the current service only creates synthetic metadata. A real backup-and-restore drill is still required.
- There is no CI workflow or deployment pipeline in the repository to run tests, Pint, frontend build, dependency audit, and migration checks on every pull request.
- Test-count records are inconsistent: README advertises 183 tests, `docs/PROGRESS.md` records 213, root `PROGRESS.md` records 230, and the most recent full-suite run in the conversation was 233 passing (2,393 assertions), followed by the pharmacy-demo tests passing separately (2 tests, 41 assertions). The full suite has not been re-run after those final additions and the restaurant-only floor-plan route guard.

## Recommended work order

### P0 — Before any live checkout or paid subscription

1. Fix server-side price/tax/discount calculation and settlement invariants in POS.
2. Change tenant billing to asynchronous pending payments; remove client-asserted proof of payment and do not activate subscriptions before confirmed settlement.
3. Implement and test authenticated, idempotent M-Pesa callback reconciliation.
4. Disable real pharmacy sales until expiry, prescription, batch, and controlled-substance policies are enforced in the POS transaction.
5. Replace backup/restore simulation with a real encrypted off-site backup and verified restore path.

### P1 — Before onboarding real businesses

1. Add tenant MFA for owners and users with high-impact permissions.
2. Make tenant and shop scopes fail closed; cover jobs, commands, listeners, route binding, and long-lived workers.
3. Add CI with full tests, Pint, `npm run build`, dependency/security scanning, and migration checks.
4. Run the suite on the intended production database engine and perform concurrent POS/inventory tests.
5. Use managed database, Redis/cache/queue, object storage, HTTPS-only cookies, explicit trusted proxies, secret management, monitoring, and tested backup retention.

### P2 — Product completeness and operating readiness

1. Finish a usable pharmacy workflow and then complete the other advertised industry modules or label them clearly as planned.
2. Implement a versioned public API only if integrations require it; otherwise align API docs with the existing web application.
3. Add browser acceptance tests for login, POS, product/image upload, billing, support, and each industry’s critical workflow.
4. Add payment, error-rate, queue backlog, backup-age, restore-drill, and tenant-isolation alerts.
5. Reconcile README, progress ledgers, platform docs, and actual route/service status from a single source of truth.

## Hosting options

BlePOS needs a persistent PHP web process, a relational database, queue workers, a scheduler, durable object storage for uploads/backups, HTTPS, and secrets management. Do not deploy the current SQLite/local-filesystem development configuration as a multi-tenant production service.

| Platform | Fit | Trade-offs for this app |
|---|---|---|
| **AWS (EC2/ECS or Elastic Beanstalk + RDS + ElastiCache + S3)** | Best control and the most direct path to a production-shaped Laravel stack. AWS documents RDS and ElastiCache in Africa (Cape Town), along with the broader regional service set. | More operational work: VPC, release automation, queue/scheduler processes, IAM, backups, monitoring, scaling, and cost controls are yours to design. Cape Town is not Nairobi, so measure real POS latency before deciding. [RDS regions](https://docs.aws.amazon.com/AmazonRDS/latest/UserGuide/Concepts.RegionsAndAvailabilityZones.html), [ElastiCache regions](https://docs.aws.amazon.com/AmazonElastiCache/latest/dg/RegionsAndAZs.html). |
| **Laravel Cloud** | Lowest Laravel-specific operations burden among these options: managed MySQL/Postgres, cache/queue, workers, object storage, and scheduler integrate with Laravel deployment. | Confirm region availability and data-location requirements for the account before choosing it; current listed regions cover the US, Canada, Europe, and Asia-Pacific, with no Africa region shown. [Deployment/resources](https://laravel.com/cloud/deploy-a-laravel-app), [regions and capabilities](https://marketing.cloud.laravel.com/). |
| **DigitalOcean App Platform + Managed Database/Valkey/Spaces** | Straightforward Git-based PaaS with web components, workers/jobs, and managed database options; suitable for a pilot or a team wanting simpler operations than AWS. | Its published datacenter and App Platform region lists do not show an Africa region, so app/database latency from Kenya should be measured. Use managed databases for production, not the development database option. [Regional availability](https://docs.digitalocean.com/platform/regional-availability/), [App Platform databases](https://docs.digitalocean.com/products/app-platform/how-to/manage-databases/), [jobs](https://docs.digitalocean.com/products/app-platform/how-to/manage-jobs/). |
| **Render** | Simple Git-connected web service, background worker, cron, managed Postgres, and Redis-compatible Key Value components. Good for staging or a lower-operations pilot. | Current listed regions are Oregon, Ohio, Virginia, Frankfurt, and Singapore; there is no Africa region listed. [Regions](https://render.com/docs/regions), [service types](https://render.com/docs/service-types), [cron jobs](https://render.com/docs/cronjobs). |

**Practical shortlist:** evaluate AWS Cape Town if regional placement and control matter most; evaluate Laravel Cloud if reducing Laravel operations work matters most. Keep the database, application, and queue close together. Before committing, compare total cost at expected concurrency, latency from Nairobi, backup/restore capabilities, support response, data-processing terms, and the exact production requirements for payment callbacks.

## Review references

- [`docs/TESTING.md`](TESTING.md) and [`phpunit.xml`](../phpunit.xml)
- [`docs/DEPLOYMENT.md`](DEPLOYMENT.md) and [`.env.example`](../.env.example)
- [`docs/SECURITY.md`](SECURITY.md) and [`docs/PLATFORM_SECURITY.md`](PLATFORM_SECURITY.md)
- [`docs/INDUSTRIES.md`](INDUSTRIES.md), [`app/Services/PharmacyService.php`](../app/Services/PharmacyService.php), and [`routes/web.php`](../routes/web.php)
- [`app/Services/InfrastructureService.php`](../app/Services/InfrastructureService.php)

## Remediation — 25 November 2026

Implementation follow-up is partial. The local suite passes with 304 tests / 2,802 assertions, Pint passes, and `npm run build` passes. MySQL/PostgreSQL CI, live provider settlement, and production recovery drills remain unverified. Item-by-item statuses and tests are recorded in [`App review 2026.md`](../App%20review%202026.md) and `docs/PROGRESS_LOG.md` L-0136–L-0146. No P0/P1 item is marked fully closed in this follow-up.
