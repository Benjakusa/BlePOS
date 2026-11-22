# BlePOS Annex A Progress

## Session header
- Last updated: 2026-11-22 18:39 UTC / 21:39 EAT
- Last agent / session ID: codex-resume-2026-11-22
- Current tier / phase: Tier A / A1–A2 acceptance completion
- Build status: Not rerun this session
- Last full test run: `php artisan test --compact` — 81 tests, 584 assertions (2026-11-22; see `PROGRESS_LOG.md` L-0087).
- Resume verification: `vendor/bin/pint --format agent` passed (2026-11-22; see `PROGRESS_LOG.md` L-0005).
- Git working branch / checkpoint: `main` contains pushed commit `9216e6416cc9ca433f2b62a66342daf1a64f0436`; `/tmp/BlePOS-push` is clean and tracks `origin/main`. The workspace `.git` remains empty.

## RESUME POINT
- Next action (exact): Continue A1/A2 acceptance review using the remaining Annex A evidence gaps; keep A3 gated.
- Preconditions / what must be true first: Super Administrator role creation now persists every seeded permission, and direct action authorization checks cover all protected routes with middleware bypass (L-0091, L-0087). Commits `23ffb51`, `133516c`, and `9216e64` are pushed.
- Files in flight: No uncommitted files in `/tmp/BlePOS-push`; the workspace `.git` remains empty. Use the clean clone for the next increment and push verified work as completed.
- First commands to run on resume: Re-read the remaining A1/A2 acceptance gaps in this file and Annex A, select a high-value unverified acceptance scenario, then verify it directly.
- Warnings: Do not start A3 before A1/A2 are verified. The workspace `.git` is empty; continue using the confirmed `/tmp/BlePOS-push` clone for commits and pushes.

## Latest session verification
| Suite | Command | Date | Passed | Failed | Skipped | Notes |
|---|---|---|---:|---:|---:|---|
| Full PHPUnit suite | `php artisan test --compact` | 2026-11-22 | 73 | 0 | 0 | 532 assertions, exit 0; see `PROGRESS_LOG.md` L-0026. |
| Built-in role permission matrix | `php artisan test --compact tests/Feature/PlatformRolePermissionMatrixTest.php` | 2026-11-22 | 1 | 0 | 0 | 9 assertions, exit 0; see `PROGRESS_LOG.md` L-0015. |
| PHP style | `vendor/bin/pint --format agent` | 2026-11-22 | Passed | — | — | Dirty mode unavailable because `.git` is empty; fallback formatter passed, see `PROGRESS_LOG.md` L-0017. |
| PHP style after route test | `vendor/bin/pint --format agent` | 2026-11-22 | Passed | — | — | See `PROGRESS_LOG.md` L-0025. |
| Progress integrity | `scripts/progress-check` | 2026-11-22 | Passed | — | — | 40 log entries validated; see `PROGRESS_LOG.md` L-0041. |
| Role boundary | `php artisan test --compact tests/Feature/PlatformRoleBoundaryTest.php` | 2026-11-22 | 8 | 0 | 0 | 20 assertions, exit 0; see `PROGRESS_LOG.md` L-0076. |
| Full PHPUnit suite | `php artisan test --compact` | 2026-11-22 | 78 | 0 | 0 | 542 assertions, exit 0; see `PROGRESS_LOG.md` L-0078. |
| Full PHPUnit suite | `php artisan test --compact` | 2026-11-22 | 78 | 0 | 0 | 542 assertions, exit 0; rerun in this session, see `PROGRESS_LOG.md` L-0079. |
| Role boundary | `php artisan test --compact tests/Feature/PlatformRoleBoundaryTest.php` | 2026-11-22 | 9 | 0 | 0 | 25 assertions, exit 0; includes the Super Administrator positive create path, see `PROGRESS_LOG.md` L-0080. |
| PHP style | `vendor/bin/pint --format agent` | 2026-11-22 | Passed | — | — | Ordered imports in the role-boundary test; see `PROGRESS_LOG.md` L-0080. |
| Full PHPUnit suite | `php artisan test --compact` | 2026-11-22 | 79 | 0 | 0 | 547 assertions, exit 0; see `PROGRESS_LOG.md` L-0080. |
| Role boundary | `php artisan test --compact tests/Feature/PlatformRoleBoundaryTest.php` | 2026-11-22 | 10 | 0 | 0 | 27 assertions, exit 0; includes a request with route permission middleware removed, see `PROGRESS_LOG.md` L-0082. |
| PHP style | `vendor/bin/pint --format agent` | 2026-11-22 | Passed | — | — | See `PROGRESS_LOG.md` L-0082. |
| Full PHPUnit suite | `php artisan test --compact` | 2026-11-22 | 80 | 0 | 0 | 549 assertions, exit 0; see `PROGRESS_LOG.md` L-0082. |
| Route authorization | `php artisan test --compact tests/Feature/PlatformRouteAuthorizationTest.php` | 2026-11-22 | 4 | 0 | 0 | 195 assertions, exit 0; direct checks cover all protected platform listing screens and invitation creation, see `PROGRESS_LOG.md` L-0084. |
| PHP style | `vendor/bin/pint --format agent` | 2026-11-22 | Passed | — | — | See `PROGRESS_LOG.md` L-0084. |
| Full PHPUnit suite | `php artisan test --compact` | 2026-11-22 | 81 | 0 | 0 | 558 assertions, exit 0; see `PROGRESS_LOG.md` L-0084. |
| Progress integrity | `scripts/progress-check` | 2026-11-22 | Passed | — | — | 84 numbered log entries validated; see `PROGRESS_LOG.md` L-0085. |
| Git synchronization | `git -C /tmp/BlePOS-push push origin main` | 2026-11-22 | Passed | — | — | Commit `23ffb513e7a8bef8e2d9d8de0f7f87a64957065b`; `origin/main` verified at the same SHA, see `PROGRESS_LOG.md` L-0086. |
| Git synchronization | `git -C /tmp/BlePOS-push push origin main` | 2026-11-22 | Passed | — | — | Commit `133516cb801bde0c5635de2d3583a5b78ae6ec20`; `origin/main` verified at the same SHA, see `PROGRESS_LOG.md` L-0090. |
| Git synchronization | `git -C /tmp/BlePOS-push push origin main` | 2026-11-22 | Passed | — | — | Commit `9216e6416cc9ca433f2b62a66342daf1a64f0436`; `origin/main` verified at the same SHA, see `PROGRESS_LOG.md` L-0093. |
| Route authorization | `php artisan test --compact tests/Feature/PlatformRouteAuthorizationTest.php` | 2026-11-22 | 4 | 0 | 0 | 221 assertions, exit 0; direct checks cover protected listing and mutation routes with route permission middleware removed, see `PROGRESS_LOG.md` L-0087. |
| PHP style | `vendor/bin/pint --format agent` | 2026-11-22 | Passed | — | — | See `PROGRESS_LOG.md` L-0087. |
| Full PHPUnit suite | `php artisan test --compact` | 2026-11-22 | 81 | 0 | 0 | 584 assertions, exit 0; see `PROGRESS_LOG.md` L-0087. |
| Role boundary | `php artisan test --compact tests/Feature/PlatformRoleBoundaryTest.php` | 2026-11-22 | 10 | 0 | 0 | 27 assertions, exit 0; Super Administrator assigns all catalogued permissions in one custom role, see `PROGRESS_LOG.md` L-0091. |
| PHP style | `vendor/bin/pint --format agent` | 2026-11-22 | Passed | — | — | See `PROGRESS_LOG.md` L-0091. |
| Full PHPUnit suite | `php artisan test --compact` | 2026-11-22 | 81 | 0 | 0 | 584 assertions, exit 0; see `PROGRESS_LOG.md` L-0091. |
| Progress integrity | `scripts/progress-check` | 2026-11-22 | Passed | — | — | 91 numbered log entries validated; see `PROGRESS_LOG.md` L-0092. |
| Progress integrity | `scripts/progress-check` | 2026-11-22 | Passed | — | — | 93 numbered log entries validated; see `PROGRESS_LOG.md` L-0094. |
| Progress integrity | `scripts/progress-check` | 2026-11-22 | Passed | — | — | 87 numbered log entries validated; see `PROGRESS_LOG.md` L-0088. |
| Progress integrity | `scripts/progress-check` | 2026-11-22 | Passed | — | — | 88 numbered log entries validated; see `PROGRESS_LOG.md` L-0089. |
| Progress integrity | `scripts/progress-check` | 2026-11-22 | Passed | — | — | 82 numbered log entries validated; see `PROGRESS_LOG.md` L-0083. |
| Progress integrity | `scripts/progress-check` | 2026-11-22 | Passed | — | — | 80 numbered log entries validated; see `PROGRESS_LOG.md` L-0081. |
| PHP style | `vendor/bin/pint --format agent` | 2026-11-22 | Passed | — | — | See `PROGRESS_LOG.md` L-0077. |

## A2 acceptance evidence — role creation denied for unprivileged built-in roles
- Status: VERIFIED for the tested direct-request denial cases; overall A2 remains PARTIAL.
- Test: `PlatformRoleBoundaryTest::test_roles_without_role_management_permission_cannot_create_roles` uses a data provider for operations administrator, billing administrator, support agent, compliance/eTIMS officer, and platform auditor. The existing Security Officer test separately verifies direct role-editor denial.
- Positive-path test: `PlatformRoleBoundaryTest::test_super_administrator_can_create_a_role_with_each_catalogued_permission_and_audit_it` verifies the response, persisted custom role, complete permission-catalogue assignment and audit record.
- Action-layer check: `PlatformPermissionAuthorizer` is shared by the route middleware and all role/role-assignment controller actions. `PlatformRoleBoundaryTest::test_role_creation_still_checks_permission_when_route_permission_middleware_is_missing` verifies a Support Agent is still denied when the permission middleware is bypassed.
- Focused result: `php artisan test --compact tests/Feature/PlatformRoleBoundaryTest.php` — 10 tests, 27 assertions, passed (L-0091).
- Full regression result: `php artisan test --compact` — 81 tests, 584 assertions, passed (L-0091).
- Style: `vendor/bin/pint --format agent` passed (L-0091).
- Direct-action evidence: A request matrix with route permission middleware removed covers each permission-protected dashboard, user, role, approval, session, security-event and audit action. Denied writes leave invitations, user status, roles, approvals, sessions and audit records unchanged (L-0087).
- Focused mutation result: `php artisan test --compact tests/Feature/PlatformRouteAuthorizationTest.php` — 4 tests, 221 assertions, passed (L-0087).
- Full regression result: `php artisan test --compact` — 81 tests, 584 assertions, passed (L-0087).
- Style: `vendor/bin/pint --format agent` passed (L-0087).
- Remaining A2 work: Super Administrator positive role creation across the full permission matrix and the complete Annex permission/API/screen matrix remain unverified.

## A2 acceptance evidence — route permissions resolve to the catalogue
- Status: VERIFIED for this acceptance increment; overall A2 remains PARTIAL.
- Test: `PlatformRouteAuthorizationTest::test_every_platform_control_route_uses_a_permission_in_the_catalogue` seeds platform permissions and asserts every protected console route's middleware permission exists.
- Focused result: `php artisan test --compact tests/Feature/PlatformRouteAuthorizationTest.php` — 3 tests, 186 assertions, passed (L-0024).
- Regression result: `php artisan test --compact` — 73 tests, 532 assertions, passed (L-0026).
- Style: `vendor/bin/pint --format agent` passed (L-0025).

## A2 acceptance evidence — built-in platform role permission matrix
- Status: VERIFIED for this acceptance increment; overall A2 remains PARTIAL.
- Backend: `database/seeders/PlatformRoleSeeder.php` (existing role definitions; unchanged).
- Test: `tests/Feature/PlatformRolePermissionMatrixTest.php` asserts the seven built-in roles and exact named grants for the six least-privilege roles, and checks that Super Administrator grants match the permission catalogue.
- Focused result: `php artisan test --compact tests/Feature/PlatformRolePermissionMatrixTest.php` — 1 test, 9 assertions, passed (L-0015).
- Regression result: `php artisan test --compact` — 73 tests, 532 assertions, passed (L-0026).
- Style: `vendor/bin/pint --format agent` passed (L-0017).
- Remaining A2 work: Direct-action coverage across each role, service/action-layer authorization, and the full permission-to-screen/API matrix remain unverified.

This tracker records implementation evidence for [Annex A](ANNEX_A.md). A feature is `VERIFIED` only after its implementation and tests pass. Documentation of a requirement does not count as implementation.

## Verified increments

### Transport security (2026-11-22)

- ✅ Platform console responses carry CSP, referrer, content-type, frame, permissions, and cross-origin opener headers (`PlatformSecurityHeaders`). CSP nonce is present on the role editor script and matches the response policy.
- ✅ HTTPS enforcement redirects platform-console HTTP requests with status 308 when enabled (`EnforcePlatformHttps`, `PLATFORM_FORCE_HTTPS`; production defaults to enabled).
- ✅ Forwarded HTTPS is ignored when the source proxy is not trusted. Trusted proxy IPs are configured through `TRUSTED_PROXIES` in `config/trustedproxy.php`.
- ✅ Focused verification passed: `vendor/bin/pint --format agent`; `php artisan test --compact tests/Feature/PlatformTransportSecurityTest.php tests/Feature/PlatformRoleNotificationTest.php` — 7 tests, 32 assertions.
- ✅ Full regression verification passed after this increment: `php artisan test --compact` — 51 tests, 262 assertions; Pint passed.
- ☐ Production HTTPS termination, allowlisted proxy behavior, and production HSTS delivery still require deployment-environment verification.

### Platform IP allowlist and CLI emergency override (2026-11-22)

- ✅ Optional platform-console allowlist accepts explicit IPv4/IPv6 addresses and CIDR networks (`PLATFORM_IP_ALLOWLIST_ENABLED`, `PLATFORM_IP_ALLOWLIST`); requests outside the configured networks are denied before login and recorded as security events.
- ✅ `php artisan platform:ip-allowlist-emergency-override --minutes=15 --reason="..."` creates a time-limited server-CLI override (1–60 minutes) and records it in both security events and the append-only audit chain.
- ✅ Focused verification passed: `vendor/bin/pint --format agent`; `php artisan test --compact tests/Feature/PlatformIpAllowlistTest.php tests/Feature/PlatformTransportSecurityTest.php` — 8 tests, 27 assertions.
- ✅ Full regression verification passed: `php artisan test --compact` — 56 tests, 275 assertions.
- ☐ Shared-cache behavior, trusted reverse-proxy deployments, firewall/VPN setup, and production HTTPS/HSTS still require deployment verification.

### Direct role authorization boundaries (2026-11-22)

- ✅ Security Officer can open the session review and is denied direct GET and POST requests to role-management endpoints without the role-management permission.
- ✅ The denied direct role-creation request does not create a role.
- ✅ Focused verification passed: `vendor/bin/pint --format agent`; `php artisan test --compact tests/Feature/PlatformRoleBoundaryTest.php` — 3 tests, 10 assertions.
- ✅ Full regression verification passed: `php artisan test --compact` — 57 tests, 279 assertions.

### Platform route authorization coverage (2026-11-22)

- ✅ Added an authorization inventory test that checks every platform control route for the platform guard, MFA, session validation, and an explicit named permission.
- ✅ Added a route invariant that checks every user, role, session, and approval mutation for both an explicit permission and step-up authentication.
- ✅ Focused verification passed: `vendor/bin/pint --format agent`; `php artisan test --compact tests/Feature/PlatformRouteAuthorizationTest.php` — 2 tests, 139 assertions.
- ✅ Full regression verification passed: `php artisan test --compact` — 59 tests, 418 assertions.

### Trusted-proxy HTTPS and production HSTS (2026-11-22)

- ✅ HTTPS forwarded by an explicitly trusted proxy is accepted without an HTTP redirect.
- ✅ Production HTTPS responses receive `Strict-Transport-Security: max-age=31536000`.
- ✅ Untrusted forwarded HTTPS remains covered and is redirected to HTTPS when enforcement is enabled.
- ✅ Focused verification passed: `vendor/bin/pint --format agent`; `php artisan test --compact tests/Feature/PlatformTransportSecurityTest.php` — 4 tests, 17 assertions.
- ✅ Full regression verification passed: `php artisan test --compact` — 60 tests, 421 assertions.
- ☐ Actual reverse-proxy configuration, edge TLS termination, browser HSTS behavior, and production deployment still need environment verification.

### Separate platform and tenant session cookies (2026-11-22)

- ✅ Platform console requests use `PLATFORM_SESSION_COOKIE`; tenant web requests use `SESSION_COOKIE`. They no longer share the same session-cookie name.
- ✅ `StartBleposSession` switches the cookie name and session lifetime/security settings for `/platform-console` and restores tenant settings for other web routes.
- ✅ Feature coverage verifies platform and tenant routes each issue only their own session cookie, including consecutive requests through the same session manager.
- ✅ Production defaults enable the Secure flag for platform and tenant cookies; HttpOnly is enabled for the platform cookie.
- ✅ Verification passed: `vendor/bin/pint --format agent`; `php artisan test --compact` — 61 tests, 430 assertions.
- ☐ Deployment-specific cookie domain/path and HTTPS-only settings still require review against the final host topology.

### Step-up authentication enforcement (2026-11-22)

- ✅ A valid current password and TOTP grant a five-minute step-up window and create an audit event.
- ✅ Invalid password or authenticator codes do not grant step-up.
- ✅ An expired step-up is rejected before a sensitive role mutation; the attempted role is not created.
- ✅ Focused verification passed: `vendor/bin/pint --format agent`; `php artisan test --compact tests/Feature/PlatformStepUpTest.php` — 3 tests, 16 assertions.
- ✅ Full regression verification passed: `php artisan test --compact` — 64 tests, 446 assertions.

### Atomic role permission changes and audit (2026-11-22)

- ✅ Role creation, edits, deletion, assignment and removal now commit with their audit-chain entries in database transactions; affected-session revocations are part of those transactions.
- ✅ Failure-injection coverage proves an audit-write failure rolls back the role, permission pivot and session revocation, and sends no change notification.
- ✅ Notifications are sent only after the transactional change and audit entry commit.
- ✅ Focused verification passed: `vendor/bin/pint --format agent`; `php artisan test --compact tests/Feature/PlatformRoleNotificationTest.php tests/Feature/PlatformRoleBoundaryTest.php tests/Feature/PlatformApprovalWorkflowTest.php` — 12 tests, 51 assertions.
- ✅ Full regression verification passed: `php artisan test --compact` — 65 tests, 453 assertions.

### Atomic platform-user status controls (2026-11-22)

- ✅ Lock, disable and reactivate changes now update account state, revoke sessions when needed, and write the audit event in a single transaction.
- ✅ Reactivation clears progressive-lockout state; self-status changes and disabling the final active Super Administrator are rejected.
- ✅ Failure-injection coverage confirms an audit failure rolls back status and session changes.
- ✅ Focused verification passed: `vendor/bin/pint --format agent`; `php artisan test --compact tests/Feature/PlatformUserStatusTest.php` — 5 tests, 16 assertions.
- ✅ Full regression verification passed: `php artisan test --compact` — 70 tests, 469 assertions.

### Audited invitation delivery failures (2026-11-22)

- ✅ Failed invitation sends cancel the unusable invitation and record both a security event and an append-only audit event with the requested reason.
- ✅ Mail transport exception details are not copied into security or audit metadata.
- ✅ Focused verification passed: `vendor/bin/pint --format agent`; `php artisan test --compact tests/Feature/PlatformInvitationWorkflowTest.php` — 6 tests, 29 assertions.
- ✅ Full regression verification passed: `php artisan test --compact` — 71 tests, 476 assertions.

## Workspace baseline

- **Application:** Laravel 13 application scaffold is present. The platform console uses `/platform-console`; tenant authentication remains on Laravel's separate `web` guard. The platform auth guard/provider and session cookie are separately configured.
- **Master prompt:** `BLEPOS_MASTER_PROMPT_v2` is referenced by Annex A but is not present in this workspace. Implementation follows the supplied Annex A and makes the minimum framework choice needed to build a runnable app.
- **Verification:** `vendor/bin/pint --format agent` passed; `php artisan route:list --except-vendor --json` booted and counted 41 routes; `php artisan list --raw` lists `platform:create-superadmin`, `platform:audit-verify`, `platform:expire-approvals`, and `platform:ip-allowlist-emergency-override`; `php artisan test --compact` passed: 71 tests, 476 assertions (2026-11-22).
- **Delivery order:** A1 and A2 are implemented in substantial part but remain `PARTIAL`; acceptance gaps remain. Tiers A3 onward are not started. Do not begin A3 until A1 and A2 are verified, as required by Annex A, Part 0.7 and Part 36.

## Feature status

`NOT STARTED` means no meaningful implementation exists. `PARTIAL` means implementation exists but required workflows or evidence remain incomplete. See Annex A, Part 0.3 for all status definitions.

| Feature | Phase / tier | Status | Backend / services | Frontend | Database | Permissions | Tests and results | Dependencies / risks | Next action |
|---|---|---|---|---|---|---|---|---|---|
| Platform authentication, MFA, sessions, login protection, CLI Super Administrator creation | A1 / Tier A | PARTIAL | `app/Http/Controllers/Platform/{AuthController,MfaController,PlatformPasswordResetController,StepUpController}.php`; `app/Http/Middleware/{EnsurePlatformMfa,TrackPlatformSession,EnsurePlatformStepUp,StartBleposSession,EnforcePlatformHttps,EnforcePlatformIpAllowlist,PlatformSecurityHeaders}.php`; `app/Services/{PlatformTotp,PlatformSecurityEvents}.php`; `app/Console/Commands/{CreatePlatformSuperadmin,EnablePlatformIpAllowlistEmergencyOverride}.php`; dedicated `platform` guard/provider and throttles in `AppServiceProvider` | Login, TOTP enrollment/challenge and recovery, password reset/change, step-up, session/security-event views | `platform_users`, `platform_sessions`, `platform_security_events`, platform reset tokens; encrypted TOTP/session identifiers; append-only platform audit | Separate platform guard and cookie; independently configured platform session lifetime/cookie attributes; MFA/session/permission/step-up/network middleware; per-IP and per-account throttles | `PlatformLoginTest`, `PlatformMfaTest`, `PlatformPasswordResetTest`, `PlatformRequirePasswordResetTest`, `PlatformSessionSecurityTest`, `PlatformUserStatusTest`, `PlatformSessionCookieTest`, `PlatformStepUpTest`, `PlatformSuperadminCommandTest`, `PlatformTotpTest`, `PlatformTransportSecurityTest`, `PlatformIpAllowlistTest`; full suite 73 tests, 532 assertions; Pint passed | ✅ Separate guard and session cookie, mandatory TOTP enrollment, one-time recovery codes, CLI-only initial Super Administrator, password reset/change, step-up, progressive lockouts and reset, lock/unfamiliar-login alerts, 30-minute idle and 8-hour absolute platform session lifetimes, session revocation, headers, configurable HTTPS and optional CIDR/IP allowlist with audited timed CLI override are implemented and covered. ☐ Shared-cache behavior, trusted reverse-proxy deployments, firewall/VPN setup, production HTTPS/HSTS, and full tenant/platform cross-authentication/tenant isolation tests await deployment and tenant implementation. SQLite audit triggers are exercised; MySQL/Postgres trigger paths are unverified. | Complete A1 acceptance/security boundary coverage and verify session, cache, proxy, firewall and HSTS behavior in deployment conditions before marking A1 verified. |
| Platform users, roles, permission catalogue, step-up, two-person approvals | A2 / Tier A | PARTIAL | `app/Http/Controllers/Platform/{PlatformUserController,PlatformRoleController,PlatformApprovalController,PlatformSessionController,PlatformSecurityEventController,PlatformAuditController}.php`; `app/Services/PlatformPermissionAuthorizer.php`; `PlatformRoleSeeder`; `PlatformAuditRecorder` / `PlatformAuditVerifier`; platform CLI commands | User/invitation administration, grouped role editor with permission diff, approval queue, active session review/revocation, security-event search, audit review/export | `platform_invitations`, `platform_roles`, `platform_permissions`, role/permission pivots, `platform_approvals`, `platform_audit_log`, audit chain lock; migrations under `database/migrations/` | Explicit granular permissions, shared permission authorizer plus route middleware and action checks, step-up, approval self-approval checks, second-person Super Administrator/MFA-reset approvals | Workflow coverage: `PlatformInvitationWorkflowTest`, `PlatformRoleNotificationTest`, `PlatformRoleBoundaryTest`, `PlatformPermissionTest`, `PlatformRouteAuthorizationTest`, `PlatformApprovalWorkflowTest`, `PlatformMfaResetApprovalTest`, `PlatformSessionSecurityTest`, `PlatformSecurityEventTest`, `PlatformAuditExportTest`, `PlatformAuditIntegrityTest`; full suite 81 tests, 584 assertions; Pint passed | ✅ Invitation accept/resend/cancel; role assignment/revocation and notifications; privilege-change session revocation; approval cancellation/reasoned expiry; Super Administrator and MFA-reset two-person flows; security-event and session UI; searchable audit with formula-safe export; direct controller permission checks remain effective when route permission middleware is bypassed, with denied writes asserting unchanged data; route-wide permission/session/MFA/step-up invariants are covered. ☐ Full Annex permission matrix and cross-role positive/negative coverage remain incomplete. | Complete the full A2 role/permission matrix and acceptance scenarios; review remaining built-in role boundaries. Only then proceed to A3. |
| Tenant registry, tenant list/detail, lifecycle, audit and security events | A3 / Tier A | NOT STARTED | None | None | None | None | None | Depends on verified platform identity/authorization and central tenant metadata design | Start after A1 and A2 are verified; implement registry, list/detail and guarded lifecycle operations |
| Tenant provisioning engine and recovery | A4 / Tier A | NOT STARTED | None | None | None | None | None | Tenant database topology, queue/runtime, and provisioning infrastructure are unspecified | Define topology and implement resumable, idempotent provisioning after A3 |
| Basic plans and subscription assignment | A5 / Tier A | NOT STARTED | None | None | None | None | None | Depends on tenant registry and authorization | Implement plan/subscription state and audited assignment |
| Module catalogue, entitlements, industry templates | A6 / Tier B | NOT STARTED | None | None | None | None | None | Depends on tenant/module architecture and Tier A | Implement versioned catalogue, dependency validation, and safe entitlement changes after Tier A |
| Platform billing, invoices, payments, refunds, dunning | A5 expansion / Tier B | NOT STARTED | None | None | None | None | None | Requires payment provider and platform eTIMS configuration/certification | Implement after basic plans; document external integration states accurately |
| Announcements and support cases/tools | A10 / Tier B | NOT STARTED | None | None | None | None | None | Requires tenant metadata, notification delivery, and platform permissions | Implement case workflows and non-impersonating assist tools |
| Infrastructure, queues, scheduler, migrations, backups and restore | A7 / Tier C | NOT STARTED | None | None | None | None | None | Runtime and infrastructure providers are unspecified | Connect real probes and build audited, isolated restore workflows |
| Security centre, IP rules, key management, incident workflows | A8 / Tier C | NOT STARTED | None | None | None | None | None | Requires verified platform auth, audit chain, key-management design and deployment controls | Implement security events and guarded operations after Tier A |
| Compliance, eTIMS oversight, data-protection requests, legal documents | A9 / Tier C | NOT STARTED | None | None | None | None | None | External KRA/eTIMS integration and jurisdictional requirements unspecified | Implement metadata-only oversight and clearly surfaced integration states |
| Platform analytics and reports | A11 / Tier D | NOT STARTED | None | None | None | None | None | Requires verified source data and privacy-preserving aggregates | Build only after its real data sources exist |
| Platform dashboard | A12 / Tier D | NOT STARTED | None | None | None | None | None | Current scaffold dashboard is a navigation landing page, not a metrics dashboard; depends on verified metrics and health probes | Add widgets alongside verified sources; show unavailable states honestly |
| Tenant role and industry dashboards | A13 / Tier D (delivered with domain phases) | NOT STARTED | None | None | None | None | None | Tenant domain modules and shop-scoped authorization are absent | Deliver each dashboard with its domain data, permissions, and tests |
| Security/isolation hardening, performance, recovery and release readiness | A14 / Tier D | NOT STARTED | None | None | None | None | None | Depends on implemented tiers, deployment environment, and operational evidence | Run Annex A acceptance/security checks after preceding tiers are complete |

## Status update policy

For each feature, replace `NOT STARTED` only when evidence supports a more specific status. Record concrete file paths, database tables/migrations, backend services/jobs, frontend screens, permissions, test commands and results, dependencies, risks, and the next action. Use `VERIFIED`, `IMPLEMENTED-UNVERIFIED`, `PARTIAL`, `BLOCKED`, `NOT STARTED`, or `FAILED` as defined in Annex A, Part 0.3. A screen rendering by itself is not verification.
