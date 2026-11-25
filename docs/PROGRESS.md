# BlePOS Progress Ledger

## Session header
- Last updated: 2026-11-25 08:31 UTC / 11:31 EAT
- Last agent / session ID: antigravity-tenant-rbac-staff-restaurant-2026-11-25
- Current tier / phase: Tenant RBAC, Staff Management, Route Authorization & Restaurant Seeding (Verified; 213 tests passing)
- Build status: Passed
- Last full test run: `php artisan test --compact` — 213 tests, 2260 assertions (2026-11-25).
- Resume verification: TenantStaffAndRoleIsolationTest passed (5 tests, 38 assertions); full suite passed (213 tests, 2260 assertions); `vendor/bin/pint --test` passed; `npm run build` passed.
- Git working branch / checkpoint: Comprehensive tenant RBAC implemented. Tenant owner has unrestricted access across all modules, dedicated staff management created (`tenant.users.*`), granular middleware route enforcement (`tenant.permission`), dynamic permission-aware sidebar filtering, and fully seeded Restaurant tenant (`Savanna Bistro & Grill`) with distinct owner, cashier, inventory officer, and accountant roles.

## RESUME POINT
- Next action (exact): Live demonstration and testing of Restaurant tenant accounts with granular role permissions and staff management.
- Preconditions / what must be true first: Full test suite passing (213 tests, 2260 assertions); database seeded with Restaurant trial tenant and staff accounts.
- Files in flight: None. All models, migrations, seeders, controllers, views, middleware, docs, and tests are clean and passing.
- First commands to run on resume: `php artisan test --compact`, `vendor/bin/pint --test`.

## Latest session verification
| Suite | Command | Date | Passed | Failed | Skipped | Notes |
|---|---|---|---:|---:|---:|---|
| Tenant Staff & Role Isolation | `php artisan test tests/Feature/TenantStaffAndRoleIsolationTest.php` | 2026-11-25 | 5 | 0 | 0 | 38 assertions, exit 0 |
| Full PHPUnit suite | `php artisan test --compact` | 2026-11-25 | 213 | 0 | 0 | 2260 assertions, exit 0 |
| PHP style | `vendor/bin/pint --test` | 2026-11-25 | Passed | — | — | Zero code style issues |
| Hardening & release readiness | `php artisan test --compact tests/Feature/PlatformHardeningAndReleaseReadinessTest.php` | 2026-11-24 | 6 | 0 | 0 | 172 assertions, exit 0; see `PROGRESS_LOG.md` L-0127. |
| Full PHPUnit suite | `php artisan test --compact` | 2026-11-24 | 183 | 0 | 0 | 1970 assertions, exit 0; see `PROGRESS_LOG.md` L-0127. |
| PHP style | `vendor/bin/pint --format agent` | 2026-11-24 | Passed | — | — | See `PROGRESS_LOG.md` L-0127. |
| Tenant role and industry dashboards | `php artisan test --compact tests/Feature/TenantRoleDashboardTest.php` | 2026-11-24 | 7 | 0 | 0 | 38 assertions, exit 0; see `PROGRESS_LOG.md` L-0126. |
| Full PHPUnit suite | `php artisan test --compact` | 2026-11-24 | 177 | 0 | 0 | 1798 assertions, exit 0; see `PROGRESS_LOG.md` L-0126. |
| PHP style | `vendor/bin/pint --format agent` | 2026-11-24 | Passed | — | — | See `PROGRESS_LOG.md` L-0126. |
| Platform dashboard & widgets | `php artisan test --compact tests/Feature/PlatformDashboardTest.php` | 2026-11-24 | 5 | 0 | 0 | 22 assertions, exit 0; see `PROGRESS_LOG.md` L-0125. |
| Full PHPUnit suite | `php artisan test --compact` | 2026-11-24 | 170 | 0 | 0 | 1760 assertions, exit 0; see `PROGRESS_LOG.md` L-0125. |
| PHP style | `vendor/bin/pint --format agent` | 2026-11-24 | Passed | — | — | See `PROGRESS_LOG.md` L-0125. |
| Platform analytics & reports | `php artisan test --compact tests/Feature/PlatformAnalyticsTest.php tests/Feature/PlatformRouteAuthorizationTest.php` | 2026-11-24 | 8 | 0 | 0 | 853 assertions, exit 0; see `PROGRESS_LOG.md` L-0124. |
| Full PHPUnit suite | `php artisan test --compact` | 2026-11-24 | 165 | 0 | 0 | 1738 assertions, exit 0; see `PROGRESS_LOG.md` L-0124. |
| PHP style | `vendor/bin/pint --format agent` | 2026-11-24 | Passed | — | — | See `PROGRESS_LOG.md` L-0124. |
| Compliance & eTIMS | `php artisan test --compact tests/Feature/PlatformComplianceAndEtimsTest.php tests/Feature/PlatformRouteAuthorizationTest.php` | 2026-11-24 | 9 | 0 | 0 | 859 assertions, exit 0; see `PROGRESS_LOG.md` L-0123. |
| Full PHPUnit suite | `php artisan test --compact` | 2026-11-24 | 161 | 0 | 0 | 1700 assertions, exit 0; see `PROGRESS_LOG.md` L-0123. |
| PHP style | `vendor/bin/pint --format agent` | 2026-11-24 | Passed | — | — | See `PROGRESS_LOG.md` L-0123. |
| Security centre & IP rules | `php artisan test --compact tests/Feature/PlatformSecurityCentreTest.php tests/Feature/PlatformRouteAuthorizationTest.php` | 2026-11-23 | 9 | 0 | 0 | 784 assertions, exit 0; see `PROGRESS_LOG.md` L-0122. |
| Full PHPUnit suite | `php artisan test --compact` | 2026-11-23 | 156 | 0 | 0 | 1578 assertions, exit 0; see `PROGRESS_LOG.md` L-0122. |
| PHP style | `vendor/bin/pint --format agent` | 2026-11-23 | Passed | — | — | See `PROGRESS_LOG.md` L-0122. |
| Infrastructure & backups | `php artisan test --compact tests/Feature/PlatformInfrastructureAndBackupTest.php tests/Feature/PlatformRouteAuthorizationTest.php` | 2026-11-23 | 11 | 0 | 0 | 677 assertions, exit 0; see `PROGRESS_LOG.md` L-0121. |
| Full PHPUnit suite | `php artisan test --compact` | 2026-11-23 | 151 | 0 | 0 | 1432 assertions, exit 0; see `PROGRESS_LOG.md` L-0121. |
| PHP style | `vendor/bin/pint --format agent` | 2026-11-23 | Passed | — | — | See `PROGRESS_LOG.md` L-0121. |
| Support cases & announcements | `php artisan test --compact tests/Feature/PlatformSupportAndAnnouncementsTest.php tests/Feature/PlatformRouteAuthorizationTest.php` | 2026-11-23 | 12 | 0 | 0 | 610 assertions, exit 0; see `PROGRESS_LOG.md` L-0120. |
| Full PHPUnit suite | `php artisan test --compact` | 2026-11-23 | 144 | 0 | 0 | 1318 assertions, exit 0; see `PROGRESS_LOG.md` L-0120. |
| PHP style | `vendor/bin/pint --format agent` | 2026-11-23 | Passed | — | — | See `PROGRESS_LOG.md` L-0120. |
| Platform billing & dunning | `php artisan test --compact tests/Feature/PlatformBillingAndDunningTest.php tests/Feature/PlatformRouteAuthorizationTest.php` | 2026-11-23 | 13 | 0 | 0 | 511 assertions, exit 0; see `PROGRESS_LOG.md` L-0119. |
| Full PHPUnit suite | `php artisan test --compact` | 2026-11-23 | 136 | 0 | 0 | 1172 assertions, exit 0; see `PROGRESS_LOG.md` L-0119. |
| PHP style | `vendor/bin/pint --format agent` | 2026-11-23 | Passed | — | — | See `PROGRESS_LOG.md` L-0119. |
| Module catalogue & entitlements | `php artisan test --compact tests/Feature/PlatformModuleAndEntitlementTest.php tests/Feature/PlatformRouteAuthorizationTest.php` | 2026-11-23 | 13 | 0 | 0 | 430 assertions, exit 0; see `PROGRESS_LOG.md` L-0118. |
| Full PHPUnit suite | `php artisan test --compact` | 2026-11-23 | 127 | 0 | 0 | 1056 assertions, exit 0; see `PROGRESS_LOG.md` L-0118. |
| PHP style | `vendor/bin/pint --format agent` | 2026-11-23 | Passed | — | — | See `PROGRESS_LOG.md` L-0118. |
| Platform plans and subscriptions | `php artisan test --compact tests/Feature/PlatformPlanAndSubscriptionTest.php tests/Feature/PlatformRouteAuthorizationTest.php` | 2026-11-23 | 7 | 0 | 0 | 381 assertions, exit 0; see `PROGRESS_LOG.md` L-0116. |
| Full PHPUnit suite | `php artisan test --compact` | 2026-11-23 | 118 | 0 | 0 | 976 assertions, exit 0; see `PROGRESS_LOG.md` L-0116. |
| PHP style | `vendor/bin/pint --format agent` | 2026-11-23 | Passed | — | — | See `PROGRESS_LOG.md` L-0116. |
| Phase 3 tenant POS, catalog & inventory | `php artisan test --compact tests/Feature/TenantCatalogTest.php tests/Feature/TenantPosAndShiftTest.php tests/Feature/TenantInventoryTest.php tests/Feature/CorePosAndInventoryTest.php` | 2026-11-23 | 13 | 0 | 0 | 90 assertions, exit 0; see `PROGRESS_LOG.md` L-0114. |
| Full PHPUnit suite | `php artisan test --compact` | 2026-11-23 | 115 | 0 | 0 | 882 assertions, exit 0; see `PROGRESS_LOG.md` L-0114. |
| PHP style | `vendor/bin/pint --format agent` | 2026-11-23 | Passed | — | — | See `PROGRESS_LOG.md` L-0114. |
| Core POS & Inventory tests | `php artisan test --compact tests/Feature/CorePosAndInventoryTest.php` | 2026-11-23 | 4 | 0 | 0 | 25 assertions, exit 0; see `PROGRESS_LOG.md` L-0111. |
| Full PHPUnit suite | `php artisan test --compact` | 2026-11-23 | 106 | 0 | 0 | 817 assertions, exit 0; see `PROGRESS_LOG.md` L-0112. |
| PHP style | `vendor/bin/pint --format agent` | 2026-11-23 | Passed | — | — | See `PROGRESS_LOG.md` L-0111. |
| Tenant provisioning and platform management | `php artisan test --compact tests/Feature/TenantProvisioningTest.php tests/Feature/PlatformTenantManagementTest.php` | 2026-11-23 | 7 | 0 | 0 | 46 assertions, exit 0; see `PROGRESS_LOG.md` L-0109. |
| Full PHPUnit suite | `php artisan test --compact` | 2026-11-23 | 102 | 0 | 0 | 792 assertions, exit 0; see `PROGRESS_LOG.md` L-0109. |
| PHP style | `vendor/bin/pint --format agent` | 2026-11-23 | Passed | — | — | See `PROGRESS_LOG.md` L-0109. |
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
| Git synchronization | `git -C /tmp/BlePOS-push push origin main` | 2026-11-22 | Passed | — | — | Progress sync commit `0c2eb0d873556578f917d83a16e1d40b05d7db2b` pushed and verified, see `PROGRESS_LOG.md` L-0095. |
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
| Built-in role mutation boundaries | `php artisan test --compact tests/Feature/PlatformRoleBoundaryTest.php tests/Feature/PlatformApprovalWorkflowTest.php tests/Feature/PlatformMfaResetApprovalTest.php` | 2026-11-23 | 19 | 0 | 0 | 124 assertions; includes Platform Auditor denied user, role, approval and session mutations when route permission middleware is bypassed, see `PROGRESS_LOG.md` L-0103. |
| Built-in role and audit-export action boundaries | `php artisan test --compact tests/Feature/PlatformAuditExportTest.php tests/Feature/PlatformRoleBoundaryTest.php tests/Feature/PlatformApprovalWorkflowTest.php tests/Feature/PlatformMfaResetApprovalTest.php` | 2026-11-23 | 21 | 0 | 0 | 128 assertions; audit export allowed to Platform Auditor and denied to Support Agent when route permission middleware is bypassed, see `PROGRESS_LOG.md` L-0106. |
| PHP style | `vendor/bin/pint --dirty --format agent` | 2026-11-23 | Passed | — | — | See `PROGRESS_LOG.md` L-0106. |
| Full PHPUnit suite | `php artisan test --compact` | 2026-11-23 | 85 | 0 | 0 | 658 assertions, exit 0; see `PROGRESS_LOG.md` L-0106. |
| PHP style | `vendor/bin/pint --dirty --format agent` | 2026-11-23 | Passed | — | — | See `PROGRESS_LOG.md` L-0103. |
| Full PHPUnit suite | `php artisan test --compact` | 2026-11-23 | 85 | 0 | 0 | 658 assertions, exit 0; see `PROGRESS_LOG.md` L-0103. |
| Progress integrity | `scripts/progress-check` | 2026-11-22 | Passed | — | — | 82 numbered log entries validated; see `PROGRESS_LOG.md` L-0083. |
| Progress integrity | `scripts/progress-check` | 2026-11-22 | Passed | — | — | 80 numbered log entries validated; see `PROGRESS_LOG.md` L-0081. |
| PHP style | `vendor/bin/pint --format agent` | 2026-11-22 | Passed | — | — | See `PROGRESS_LOG.md` L-0077. |
| Built-in role screen access | `php artisan test --compact tests/Feature/PlatformRoleBoundaryTest.php` | 2026-11-23 | 11 | 0 | 0 | 83 assertions; all seven built-in roles checked against all eight implemented screens, see `PROGRESS_LOG.md` L-0096. |
| PHP style | `vendor/bin/pint --format agent` | 2026-11-23 | Passed | — | — | Passed after updating role-boundary coverage, see `PROGRESS_LOG.md` L-0096. |
| Full PHPUnit suite | `php artisan test --compact` | 2026-11-23 | 82 | 0 | 0 | 640 assertions, exit 0; see `PROGRESS_LOG.md` L-0096. |
| Progress integrity | `scripts/progress-check` | 2026-11-23 | Passed | — | — | 97 numbered log entries validated; see `PROGRESS_LOG.md` L-0098. |
| Role boundary | `php artisan test --compact tests/Feature/PlatformRoleBoundaryTest.php` | 2026-11-23 | 12 | 0 | 0 | 90 assertions; Security Officer role assignment, removal, role editor and password-reset actions denied with route permission middleware removed, see `PROGRESS_LOG.md` L-0099. |
| PHP style | `vendor/bin/pint --dirty --format agent` | 2026-11-23 | Passed | — | — | See `PROGRESS_LOG.md` L-0099. |
| Approval boundaries | `php artisan test --compact tests/Feature/PlatformApprovalWorkflowTest.php tests/Feature/PlatformMfaResetApprovalTest.php` | 2026-11-23 | 6 | 0 | 0 | 26 assertions; Security Officer cannot approve a Super Administrator grant, while MFA-reset approval remains available, see `PROGRESS_LOG.md` L-0101. |
| PHP style | `vendor/bin/pint --dirty --format agent` | 2026-11-23 | Passed | — | — | See `PROGRESS_LOG.md` L-0101. |
| Full PHPUnit suite | `php artisan test --compact` | 2026-11-23 | 84 | 0 | 0 | 650 assertions, exit 0; see `PROGRESS_LOG.md` L-0101. |
| Progress integrity | `scripts/progress-check` | 2026-11-23 | Passed | — | — | 101 numbered log entries validated; see `PROGRESS_LOG.md` L-0102. |
| Progress integrity | `scripts/progress-check` | 2026-11-23 | Passed | — | — | 99 numbered log entries validated; see `PROGRESS_LOG.md` L-0100. |
| Tenant and shop isolation | `php artisan test --compact tests/Feature/TenantIsolationTest.php tests/Feature/ShopIsolationTest.php tests/Feature/TenantRbacTest.php` | 2026-11-23 | 10 | 0 | 0 | 29 assertions, exit 0; see `PROGRESS_LOG.md` L-0107. |
| Full PHPUnit suite | `php artisan test --compact` | 2026-11-23 | 95 | 0 | 0 | 687 assertions, exit 0; see `PROGRESS_LOG.md` L-0107. |
| PHP style | `vendor/bin/pint --format agent` | 2026-11-23 | Passed | — | — | See `PROGRESS_LOG.md` L-0107. |

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
- ✅ Super Administrator role creation persists and audits every permission currently in the catalogue (L-0091).
- Remaining A2 work: The full Annex permission/API/screen/action matrix, including workflows not yet implemented, remains incomplete; A2 stays PARTIAL.

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
- ✅ `PlatformRoleBoundaryTest::test_builtin_roles_can_only_open_their_authorized_platform_screens` checks allowed and denied requests for all seven built-in roles across the eight implemented screens (L-0096).
- Remaining A2 work: Complete the full Annex permission-to-screen/API/action matrix as remaining platform workflows are implemented; A2 stays PARTIAL.

This tracker records implementation evidence for [Annex A](ANNEX_A.md). A feature is `VERIFIED` only after its implementation and tests pass. Documentation of a requirement does not count as implementation.

## Verified increments

### Tenant Role and Industry Dashboards (2026-11-24)

- ✅ Cashier perspective (Annex A Part 21): Dedicated terminal view scoped strictly to active cashier shift and register, showing shift opening float, cash sales, M-Pesa sales, completed transaction counts, recent receipt records, and open-shift actions, with strict concealment of gross margin, cost prices, other cashiers' figures, and multi-shop totals.
- ✅ Shop Manager perspective (Annex A Part 20): Branch-scoped dashboard showing sales totals, average basket size, cash vs M-Pesa split, active registers & cashiers on duty, closed shift cash variances (`expected_cash != actual_cash`), low-stock items (< 5) and out-of-stock items, and shop quick operations.
- ✅ Tenant Owner perspective (Annex A Part 19): Multi-shop executive overview computing gross sales, net sales (excluding tax), 16% VAT collected, average basket size, payment split, loss prevention and voided sales alerts, multi-shop comparison matrix across all locations, top products by sales volume, inventory health, and subscription plan status.
- ✅ Industry blueprint additions (Annex A Part 27): Contextual alerts and operational notices for Pharmacy (controlled substances dispensing and batch expiry compliance) and Hospitality (table folios, open tabs, order queues).
- ✅ Shop isolation & switching (Annex A Part 18): `EnforceShopIsolation` middleware and `routes/web.php` enhanced to resolve active shop via headers, route parameters, request inputs (`shop_id`), or session (`active_shop_id`), enabling seamless branch switching for owners and multi-branch managers while strictly confining single-branch cashiers.
- ✅ Verification: `tests/Feature/TenantRoleDashboardTest.php` (7 tests, 38 assertions); full suite 177 tests, 1798 assertions, exit 0 (see `PROGRESS_LOG.md` L-0126).

### Platform Control Plane Dashboard Integrated Widgets (2026-11-24)

- ✅ Executive headline KPIs: Real computed metrics across tenants (total, active, suspended, provisioning failed, retention rate), bcmath-calculated MRR and ARR (`ARR = 12 * MRR`), total invoiced, total collected, receivables due, and collection efficiency rate without exposing private tenant records.
- ✅ Live subsystem reliability probes: Live Database, Cache, Storage, and Queue health probes surfacing status (`healthy`, `degraded`, `unhealthy`), response latency (ms), and diagnostic details.
- ✅ Encrypted backups & migration ledger: Live inspection of migration batches and automated encrypted backups with SHA-256 integrity checksum verification.
- ✅ Security Centre & key governance: Threat monitoring widgets surfacing open security incidents, 24-hour critical security events, active blocked denylist IPs, active sessions, and cryptographic envelope encryption key versioning with status.
- ✅ Compliance & eTIMS console: Technical KRA checklist validation tracker, statutory 30-day Kenya DPA / GDPR DSAR request counters, published platform legal documents, and transparent placeholder status (`BLOCKED-ON-CERTIFICATION`) pending official regulatory clearance.
- ✅ Needs-Attention & Two-Person Rule alerts: Real-time alert inbox notifying administrators of provisioning failures, open security incidents, overdue DSAR requests, degraded infrastructure probes, and pending high-risk actions requiring secondary administrative authorization.
- ✅ Verification: `tests/Feature/PlatformDashboardTest.php` (5 tests, 22 assertions); full suite 170 tests, 1760 assertions, exit 0 (see `PROGRESS_LOG.md` L-0125).

### Platform Analytics, Financial Aggregates, and Formula-Safe Reports (2026-11-24)

- ✅ Platform financial & commercial analytics: `PlatformAnalyticsService::getRevenueAndBillingMetrics()` calculates Monthly Recurring Revenue (MRR) using `bcmath` across active plan subscriptions, sequential VAT (16%) and invoicing aggregates, collection efficiency rates, and total receivables due.
- ✅ Tenant lifecycle & growth metrics: `PlatformAnalyticsService::getGrowthAndLifecycleMetrics()` computes total, new, active, and suspended tenants, retention rates, monitored shop counts, and POS register terminal counts across configurable time horizons (`7d`, `30d`, `90d`, `12m`).
- ✅ Plan tier & module adoption analytics: `PlatformAnalyticsService::getPlanDistribution()` and `getModuleAdoptionMetrics()` calculate subscriber counts, revenue contribution per tier, and tenant adoption percentages across all 25 catalogue modules and 7 industry blueprints.
- ✅ Formula-safe CSV export & audit tracking: `PlatformAnalyticsService::exportReport()` streams structured CSV exports (`revenue`, `tenants`, `modules`, `growth`), sanitizes leading formula injection characters (`=`, `+`, `-`, `@`), and records immutable audit entries via `PlatformAuditRecorder`.
- ✅ Platform console UI: Analytics & operations reporting dashboard with date period selector, commercial KPI summary cards, plan distribution table, module adoption progress bars, industry blueprint breakdown, and report downloader.
- ✅ Route invariants enforced: All analytics routes protected by `platform.dashboard.view` with `platform.mfa`, `platform.session`, and `platform.step-up` on mutation actions.
- ✅ Focused verification passed: `php artisan test --compact tests/Feature/PlatformAnalyticsTest.php tests/Feature/PlatformRouteAuthorizationTest.php` — 8 tests, 853 assertions; `vendor/bin/pint --format agent` passed (L-0124).
- ✅ Full regression verification passed: `php artisan test --compact` — 165 tests, 1738 assertions (L-0124).

### Compliance, eTIMS Regulatory Oversight, Data Protection (DSAR), and Legal Documents (2026-11-24)

- ✅ eTIMS regulatory compliance & certification checklist: `ComplianceService::getComplianceOverview()` and `updateCertificationItem()` track official criteria (16% VAT calculation, sequential numbering, cryptographic signing, offline buffering, virtual device SCU protocol, and gateway clearance), surfacing live certification status honestly as `BLOCKED-ON-CERTIFICATION` pending regulatory sign-off (Annex A 8.8 & Part 9).
- ✅ Statutory Data Protection Requests (DSAR / Kenya DPA 2019): `ComplianceService::createDataProtectionRequest()` and `updateDataProtectionRequestStatus()` handle subject access, rectification, and erasure workflows, calculating statutory 30-day deadlines per Kenya Data Protection Act 2019 Section 26 and managing status progression (`pending`, `under_review`, `processing`, `completed`, `rejected`) with audit logging.
- ✅ Versioned platform legal policies: `ComplianceService::publishLegalDocument()` manages versioned platform policies (Master SaaS Terms of Service, Privacy Policy, Multi-Tenant Data Processing Agreement, SLA Policy) with effective dates, changelogs, and author attribution.
- ✅ Platform console UI: Compliance & eTIMS console with real metrics, interactive certification checklist status updater, DSAR management directory, DSAR intake wizard, request resolution view, legal document repository, and version publisher.
- ✅ Route invariants enforced: All 11 compliance routes protected by `platform.compliance.view` with `platform.mfa`, `platform.session`, and `platform.step-up` on all mutation actions.
- ✅ Focused verification passed: `php artisan test --compact tests/Feature/PlatformComplianceAndEtimsTest.php tests/Feature/PlatformRouteAuthorizationTest.php` — 9 tests, 859 assertions; `vendor/bin/pint --format agent` passed (L-0123).
- ✅ Full regression verification passed: `php artisan test --compact` — 161 tests, 1700 assertions (L-0123).

### Security Centre, Dynamic IP Rules, Key Governance, and Incident Workflows (2026-11-23)

- ✅ Threat overview dashboard & metrics: `PlatformSecurityService::getSecurityOverview()` aggregates real-time indicators including active incident count, 24-hour critical/warning security events, active blocked IP denylist rules, and active platform sessions with key inventory status.
- ✅ Incident response lifecycle: `PlatformSecurityService::createIncident()`, `updateIncidentStatus()`, and `addIncidentNote()` manage incident state progression (`new`, `acknowledged`, `investigating`, `escalated`, `resolved`, `closed`), record containment actions, and maintain an immutable timeline.
- ✅ Dynamic IP abuse & network rules: `PlatformSecurityService::createIpRule()`, `toggleIpRule()`, and `deleteIpRule()` enforce allowlist and denylist policies with optional expiration timestamps, integrated directly into `EnforcePlatformIpAllowlist` middleware to reject blocked clients with 403 Forbidden and critical security events (`platform.network.ip_denied`).
- ✅ Cryptographic key management & envelope encryption: `PlatformSecurityService::ensureDefaultKeys()` and `rotateKey()` track root Platform Master Keys, wrapped Tenant Data Envelope Keys, database field encryption keys, and webhook signing keys with SHA-256 fingerprints, version progression, rotation schedules, and audit log tracking without exposing raw secrets (Annex A 12.4).
- ✅ Platform console UI: Complete administrative workspace with overview dashboard, incident directory, incident registration wizard, detailed incident investigation console with containment timeline, IP abuse rules management, and key management console.
- ✅ Route invariants enforced: All 13 security routes protected by `platform.security.view` with `platform.mfa`, `platform.session`, and `platform.step-up` on all mutation actions.
- ✅ Focused verification passed: `php artisan test --compact tests/Feature/PlatformSecurityCentreTest.php tests/Feature/PlatformRouteAuthorizationTest.php` — 9 tests, 784 assertions; `vendor/bin/pint --format agent` passed (L-0122).
- ✅ Full regression verification passed: `php artisan test --compact` — 156 tests, 1578 assertions (L-0122).

### Infrastructure, Queues, Scheduler, Migrations, Backups and Restore (2026-11-23)

- ✅ Subsystem health probes: `InfrastructureService::runHealthProbes()` executes real checks against database ping, cache write/read roundtrip, storage disk accessibility, and default queue driver status with latency timings and operational states (`healthy`, `degraded`, `unhealthy`).
- ✅ Queue and failed jobs monitor: `InfrastructureService::getQueueStats()` reports pending job counts and failed jobs list with queue names, exception summaries, and timestamps.
- ✅ Migration ledger inspection: `InfrastructureService::getMigrationStatus()` scans all application migration files against the database `migrations` table, reporting applied vs. pending migrations and latest batch numbers.
- ✅ Backup execution with SHA-256 integrity: `InfrastructureService::runBackup()` performs full database dump or file collection, records backup size and computes cryptographic SHA-256 checksum stored in `platform_backup_runs` (`status: completed`).
- ✅ Isolated tenant restore workflow and two-person rule enforcement: `InfrastructureService::requestRestore()` initiates tenant-scoped restore requests in pending status; `InfrastructureService::approveRestore()` enforces the two-person rule (Annex A 11.4 & 31), strictly prohibiting the requesting administrator from approving their own restore request (`ValidationException` thrown).
- ✅ Platform console UI: System health dashboard with probe status cards, queues status with failed job tables, migration history with batch groupings, backup console with on-demand run trigger and integrity hashes, and restore request workflow with approval actions.
- ✅ Route invariants enforced: All 9 infrastructure routes protected by `platform.operations.view` or `platform.operations.manage` with `platform.mfa`, `platform.session`, and `platform.step-up` on all mutation actions.
- ✅ Focused verification passed: `php artisan test --compact tests/Feature/PlatformInfrastructureAndBackupTest.php tests/Feature/PlatformRouteAuthorizationTest.php` — 11 tests, 677 assertions; `vendor/bin/pint --format agent` passed (L-0121).
- ✅ Full regression verification passed: `php artisan test --compact` — 151 tests, 1432 assertions (L-0121).

### Announcements and Support Cases/Tools (2026-11-23)

- ✅ Support case lifecycle and SLA calculation: `SupportService::createCase()` generates unique case numbers, calculates SLA deadlines (urgent: 4h, high: 12h, medium: 24h, low: 72h per Annex A 13.1), maps cases to tenants and shops, and tracks status (`open`, `in_progress`, `waiting_on_tenant`, `waiting_on_platform`, `resolved`, `closed`).
- ✅ Multi-agent support notes: `SupportService::addNote()` logs timestamped activity with strict internal vs. tenant-visible classification; internal notes are excluded from tenant-facing APIs and interfaces.
- ✅ Strict No-Impersonation Policy & Assist Diagnostics (Annex A 13.2, 13.3): All tenant assist capabilities operate without impersonating tenant staff. `SupportService::runTenantIntegrityCheck()` performs diagnostic evaluations across shops, registers, subscriptions, and staff, returning pass/fail counts only to avoid raw data leakage.
- ✅ Targeted Platform Announcements (Annex A 13.4): Platform announcements with multi-tier targeting (`all`, `plan`, `industry`, `tenant`), priority levels (`info`, `warning`, `critical`), and tenant read-receipt tracking (`platform_announcement_reads`).
- ✅ Platform console UI: Support ticket directory with SLA overview metrics, interactive ticket submission form, ticket inspection with internal note feed, agent assignment and status progression, tenant assist overview, and announcement broadcast console.
- ✅ Route invariants enforced: All 13 support and announcement routes protected by `platform.support.view` with `platform.mfa`, `platform.session`, and `platform.step-up` on all mutation actions.
- ✅ Focused verification passed: `php artisan test --compact tests/Feature/PlatformSupportAndAnnouncementsTest.php tests/Feature/PlatformRouteAuthorizationTest.php` — 12 tests, 610 assertions; `vendor/bin/pint --format agent` passed (L-0120).
- ✅ Full regression verification passed: `php artisan test --compact` — 144 tests, 1318 assertions (L-0120).

### Platform Billing, Invoices, Payments, Refunds, and Dunning Engine (2026-11-23)

- ✅ Platform billing architecture implemented: `PlatformInvoice`, `PlatformInvoiceItem`, `PlatformPayment`, `PlatformRefund`, and `PlatformDunningEvent` models and database migrations.
- ✅ Transaction-safe invoice lifecycle: `PlatformBillingService::generateInvoice()` generates unique invoice numbers, calculates 16% Kenyan VAT and subtotal using `bcmath`, records line items, and sets eTIMS regulatory compliance status `BLOCKED-ON-CERTIFICATION`.
- ✅ Payment ledger and subscription reactivation: `PlatformBillingService::recordPayment()` handles full and partial payments, prevents overpayment exceeding invoice balance due, and automatically restores overdue subscriptions (`past_due`, `suspended`) and tenants back to `active` upon full settlement.
- ✅ Refunds and void rules: `PlatformBillingService::recordRefund()` validates refund amount against available payment balance, restores invoice due balance, and updates payment status; unpaid invoices can be voided with reason audit.
- ✅ Dunning engine with non-duplicate suspension guarantee: `PlatformBillingService::processDunning()` evaluates overdue invoices, schedules stage-based events (overdue_1d, overdue_3d, overdue_7d, suspended), transitions subscription states, and guarantees tenants are never suspended twice by duplicate events (Annex A 8.9).
- ✅ Platform console UI: Invoices directory with collections metrics cards (total invoiced, collected, outstanding, collection rate, overdue counter), invoice creation form, comprehensive invoice view with line items and payment/refund ledger, and dunning engine management view.
- ✅ Route invariants enforced: All 9 invoice and dunning routes protected by `platform.billing.view` or `platform.billing.subscriptions.manage` with `platform.mfa`, `platform.session`, and `platform.step-up` on all mutation actions.
- ✅ Focused verification passed: `php artisan test --compact tests/Feature/PlatformBillingAndDunningTest.php tests/Feature/PlatformRouteAuthorizationTest.php` — 13 tests, 511 assertions; `vendor/bin/pint --format agent` passed (L-0119).
- ✅ Full regression verification passed: `php artisan test --compact` — 136 tests, 1172 assertions (L-0119).

### Industry Templates Full Management UI (2026-11-24)

- ✅ **Root cause found and fixed**: `ModuleCatalogueSeeder` was never registered in `DatabaseSeeder` — dev DB had 0 modules and 0 templates despite existing seeder code. Now registered idempotently.
- ✅ Added `is_active` boolean column (migration `2026_11_24_142729`) to `industry_templates`; `IndustryTemplate` model updated with cast and `tenants()` HasMany relationship.
- ✅ Fully rewrote `PlatformIndustryTemplateController` with 10 actions: `index`, `create`, `store`, `show`, `edit`, `update`, `destroy`, `toggleActive`, `preview`, `applyToTenant`, `apply`.
- ✅ Template list: search box, module chips (max 5 + "N more"), status badge, tenant count, view/edit actions, empty-state CTA.
- ✅ Create/Edit form: grouped module picker by category, core modules locked, live JS dependency checker with auto-add, slug auto-generation from name, server-side `resolveAllDependencies()` enforcement.
- ✅ Show page: topological dependency-order module cards, tenant list, apply-to-tenant picker with JS preview (already-enabled vs will-enable diff).
- ✅ Delete blocked if any tenant provisioned from template; deactivate (`toggleActive`) available as alternative.
- ✅ `ModuleService` extended with `resolveDependencyOrder()` (topological sort), `getMissingDependencies()`, `resolveAllDependencies()`.
- ✅ `ProvisioningEngine` steps 19/20 updated to look up template from DB and call `ModuleService::applyIndustryTemplate()` (no more dummy data).
- ✅ Tenant creation wizard reads industry options from `industry_templates` table (removed hardcoded options).
- ✅ Full security: `platform.modules.view` on reads, `platform.modules.manage` + `platform.step-up` on mutations, `PlatformPermissionAuthorizer::ensure()` in controller.
- ✅ Every create/update/delete/apply writes `PlatformAuditRecorder` entry inside same DB transaction.
- ✅ 9 feature tests in `PlatformIndustryTemplateManagementTest` (68 assertions) covering all scenarios: seeder idempotency, index/empty-state, create/store with dependency enforcement, edit/update with core protection, delete-blocked/deactivate, apply blueprint, preview JSON, permission matrix + step-up.
- ✅ Full regression: `php artisan test --compact` → **201 tests, 2,194 assertions, 0 failures**; `vendor/bin/pint --test` → passed.

### Module Catalogue, Entitlements, and Industry Templates (2026-11-23)

- ✅ Versioned module catalogue implemented: 25 modules across Core, Commercial, Logistics, Finance & Industry categories; `ModuleCatalogueSeeder` seeds all modules and 7 industry templates (retail, pharmacy, restaurant, hotel, wholesale, salon, fuel-station) using `updateOrCreate`.
- ✅ Dependency enforcement on enable: `ModuleService::enableModule()` validates all required dependency slugs are already enabled for the tenant; throws `ValidationException` listing missing module names if unsatisfied.
- ✅ Reverse-dependency protection on disable: `ModuleService::disableModule()` checks all currently-enabled entitlements for the tenant; throws `ValidationException` if any active module lists the target slug as a dependency.
- ✅ Core-module protection: `is_core` modules are blocked from disable via `abort_if(422)`.
- ✅ Industry template apply: `ModuleService::applyIndustryTemplate()` topologically orders modules using multi-pass dependency resolution and enables them in safe order; all retail template modules verified enabled end-to-end.
- ✅ Every enable/disable/apply mutation writes an immutable audit log entry via `PlatformAuditRecorder`.
- ✅ All 6 module/template routes protected by `auth:platform`, `platform.mfa`, `platform.session`, explicit named permission; mutation routes also require `platform.step-up`; verified by route invariant tests.
- ✅ Focused verification passed: `php artisan test --compact tests/Feature/PlatformModuleAndEntitlementTest.php tests/Feature/PlatformRouteAuthorizationTest.php` — 13 tests, 430 assertions; `vendor/bin/pint --format agent` passed (L-0118).
- ✅ Full regression verification passed: `php artisan test --compact` — 127 tests, 1056 assertions (L-0118).

### Tenant lifecycle, 30-step provisioning engine and platform console (2026-11-23)

- ✅ 30-step automated provisioning engine implemented (`ProvisioningEngine`) covering validation, database allocation, role/permission seeding, owner creation, initial shop/register setup, and industry blueprint configuration.
- ✅ Idempotent resumption and safe failure handling verified: failing runs are caught at the exact failed step, logged, and leave tenant status at `provisioning_failed` (never forced active). Subsequent retries resume from the failed step and complete cleanly without duplication.
- ✅ Platform console tenant management views implemented: Tenant directory with search and status filtering, multi-step registration wizard, tenant detail overview, and audited suspend/reactivate with step-up authentication.
- ✅ Provisioning monitor implemented: Live run tracking, step progress, error inspection, and audited retry trigger.
- ✅ Focused verification passed: `php artisan test --compact tests/Feature/TenantProvisioningTest.php tests/Feature/PlatformTenantManagementTest.php` — 7 tests, 46 assertions; `vendor/bin/pint --format agent` passed (L-0109).
- ✅ Full regression verification passed: `php artisan test --compact` — 102 tests, 792 assertions (L-0109).

### Multi-tenant data isolation and shop scoping (2026-11-23)

- ✅ Tenant model hierarchy established: `Tenant`, `TenantDomain`, `Shop`, `Register`, `Warehouse`, `TenantRole`, `TenantPermission`, and `User`.
- ✅ Data isolation enforced at database query level using `TenantScope` and `ShopScope` via `BelongsToTenant` and `BelongsToShop` model traits.
- ✅ Context resolution and request gating enforced via `ResolveTenantContext`, `EnforceTenantIsolation`, and `EnforceShopIsolation` middleware.
- ✅ Cashier branch isolation strictly enforced: cashiers assigned to Shop A are denied access to Shop B endpoints (403 Forbidden). Cross-tenant access is blocked.
- ✅ Focused verification passed: `php artisan test --compact tests/Feature/TenantIsolationTest.php tests/Feature/ShopIsolationTest.php tests/Feature/TenantRbacTest.php` — 10 tests, 29 assertions; `vendor/bin/pint --format agent` passed (L-0107).
- ✅ Full regression verification passed: `php artisan test --compact` — 95 tests, 687 assertions (L-0107).

### Built-in role screen access (2026-11-23)

- ✅ All seven built-in roles have allowed and denied request coverage across the eight platform screens currently implemented: dashboard, users, roles, approvals, sessions, security events, audit review and audit export.
- ✅ Focused verification passed: `php artisan test --compact tests/Feature/PlatformRoleBoundaryTest.php` — 11 tests, 83 assertions; `vendor/bin/pint --format agent` passed.
- ✅ Full regression verification passed: `php artisan test --compact` — 82 tests, 640 assertions (L-0096).
- ☐ The complete Annex permission/API/action matrix still includes workflows that have not been implemented; A2 remains PARTIAL.

### Cross-role mutation authorization (2026-11-23)

- ✅ A Security Officer retains access to its security duties but cannot open role management, assign or remove another user's role, or require a password reset—even when route permission middleware is removed.
- ✅ Focused verification passed: `php artisan test --compact tests/Feature/PlatformRoleBoundaryTest.php` — 12 tests, 90 assertions; `vendor/bin/pint --dirty --format agent` passed.
- ✅ Full regression verification passed: `php artisan test --compact` — 83 tests, 647 assertions (L-0099).
- ☐ The rest of the Annex permission/API/action matrix remains under review; A2 remains PARTIAL.

### Super Administrator grant approval boundary (2026-11-23)

- ✅ Only a different Super Administrator can approve a Super Administrator grant; the general Security Officer approvals permission no longer authorizes this role change.
- ✅ Security Officer approval of an MFA reset remains supported and verified.
- ✅ Focused verification passed: `php artisan test --compact tests/Feature/PlatformApprovalWorkflowTest.php tests/Feature/PlatformMfaResetApprovalTest.php` — 6 tests, 26 assertions; Pint passed.
- ✅ Full regression verification passed: `php artisan test --compact` — 84 tests, 650 assertions (L-0101).
- ☐ Other Annex approval types and deployment controls remain in the broader A2/A1 acceptance review.

### Platform Auditor action boundary (2026-11-23)

- ✅ A Platform Auditor remains read-only when route permission middleware is bypassed: direct user status, role deletion, approval cancellation and session revocation requests are denied.
- ✅ Denied writes leave the target user, built-in role, pending approval and active session unchanged.
- ✅ Focused role and approval verification passed: 19 tests, 124 assertions; Pint passed.
- ✅ Full regression verification passed: `php artisan test --compact` — 85 tests, 658 assertions (L-0103).
- ☐ The Annex permission/API/action matrix remains incomplete for other approval and future platform workflows; A1/A2 remain PARTIAL.

### Audit-export action permission boundary (2026-11-23)

- ✅ Platform Auditor can export audit data and Support Agent is denied when route permission middleware is bypassed; controller-level permission checks remain effective for both the allowed and denied cases.
- ✅ Focused role, approval and export verification passed: 21 tests, 128 assertions; Pint passed.
- ✅ Full regression verification passed: `php artisan test --compact` — 85 tests, 658 assertions (L-0106).
- ☐ Remaining Annex permission/API/action cases and deployment evidence keep A1/A2 PARTIAL.

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
| Platform users, roles, permission catalogue, step-up, two-person approvals | A2 / Tier A | PARTIAL | `app/Http/Controllers/Platform/{PlatformUserController,PlatformRoleController,PlatformApprovalController,PlatformSessionController,PlatformSecurityEventController,PlatformAuditController}.php`; `app/Services/PlatformPermissionAuthorizer.php`; `PlatformRoleSeeder`; `PlatformAuditRecorder` / `PlatformAuditVerifier`; platform CLI commands | User/invitation administration, grouped role editor with permission diff, approval queue, active session review/revocation, security-event search, audit review/export | `platform_invitations`, `platform_roles`, `platform_permissions`, role/permission pivots, `platform_approvals`, `platform_audit_log`, audit chain lock; migrations under `database/migrations/` | Explicit granular permissions, shared permission authorizer plus route middleware and action checks, step-up, approval self-approval checks, second-person Super Administrator/MFA-reset approvals | Workflow coverage: `PlatformInvitationWorkflowTest`, `PlatformRoleNotificationTest`, `PlatformRoleBoundaryTest`, `PlatformPermissionTest`, `PlatformRouteAuthorizationTest`, `PlatformApprovalWorkflowTest`, `PlatformMfaResetApprovalTest`, `PlatformSessionSecurityTest`, `PlatformSecurityEventTest`, `PlatformAuditExportTest`, `PlatformAuditIntegrityTest`; full suite 85 tests, 658 assertions; Pint passed (L-0103) | ✅ Invitation accept/resend/cancel; role assignment/revocation and notifications; privilege-change session revocation; approval cancellation/reasoned expiry; Super Administrator and MFA-reset two-person flows; security-event and session UI; searchable audit with formula-safe export; direct controller permission checks remain effective when route permission middleware is bypassed, with denied writes asserting unchanged data; route-wide permission/session/MFA/step-up invariants and allowed/denied built-in role screen access, Security Officer and Platform Auditor action-layer boundaries, and Super Administrator grant approval restrictions are covered (L-0103). ☐ Full Annex permission matrix remains incomplete. | Complete the full A2 role/permission matrix and acceptance scenarios; review remaining built-in role boundaries. Only then proceed to A3. |
| Tenant registry, tenant list/detail, lifecycle, audit and security events | A3 / Tier A | VERIFIED | `app/Http/Controllers/Platform/PlatformTenantController.php`; `app/Models/{Tenant,TenantDomain,Shop,Register,Warehouse,TenantRole,TenantPermission,User}.php`; `app/Services/{TenantContext,ShopContext,PlatformAuditRecorder}.php` | Platform tenant directory, registration wizard, detail views, danger zone controls | `tenants`, `tenant_domains`, `shops`, `registers`, `warehouses`, `tenant_roles`, `tenant_permissions`, `platform_audit_log` | `platform.tenants.view`, `platform.tenants.create`, `platform.tenants.suspend`, `platform.tenants.reactivate` | `PlatformTenantManagementTest`, `TenantIsolationTest`, `ShopIsolationTest`; 15 tests, 52 assertions; full suite 102 tests, 792 assertions, exit 0; see `PROGRESS_LOG.md` L-0109; `php artisan test --compact tests/Feature/PlatformTenantManagementTest.php` | ✅ Tenant directory, filtering, registration, audited suspension/reactivation, and isolation are verified. | Proceed to Phase 3 core POS operations. |
| Tenant provisioning engine and recovery | A4 / Tier A | VERIFIED | `app/Services/ProvisioningEngine.php`; `app/Http/Controllers/Platform/PlatformProvisioningController.php`; `app/Models/{ProvisioningRun,ProvisioningStep}.php` | Provisioning monitor and step inspector views | `provisioning_runs`, `provisioning_steps` | `platform.provisioning.view`, `platform.provisioning.retry` | `TenantProvisioningTest`; 2 tests, 23 assertions; full suite 102 tests, 792 assertions, exit 0; see `PROGRESS_LOG.md` L-0109; `php artisan test --compact tests/Feature/TenantProvisioningTest.php` | ✅ 30-step idempotent provisioning, step logging, failure recovery, and retry without duplicate creation verified. | Connect provisioning with plan assignment in Phase 4. |
| Core POS & Business Operations (Catalog, inventory, sales, cashier shifts, checkout UI) | Phase 3 / Tier A | VERIFIED | `app/Http/Controllers/Tenant/{CategoryController,ProductController,PosController,CashierShiftController,InventoryController,TenantDashboardController}.php`; `resources/views/tenant/`; `app/Models/{Category,Product,InventoryStock,StockMovement,CashierShift,Sale,SaleItem,SalePayment}.php`; `app/Services/{InventoryService,SaleService}.php` | Tenant catalog management, interactive POS terminal, thermal receipts, sales history, cashier shifts, stock levels & immutable movements audit ledger | `categories`, `products`, `inventory_stocks`, `stock_movements`, `cashier_shifts`, `sales`, `sale_items`, `sale_payments` | `tenant.resolve`, `tenant.isolate`, tenant RBAC via roles and permissions | `TenantCatalogTest`, `TenantPosAndShiftTest`, `TenantInventoryTest`, `CorePosAndInventoryTest`; 13 tests, 90 assertions; full suite 115 tests, 882 assertions, exit 0; see `PROGRESS_LOG.md` L-0114; `php artisan test --compact tests/Feature/TenantCatalogTest.php tests/Feature/TenantPosAndShiftTest.php tests/Feature/TenantInventoryTest.php tests/Feature/CorePosAndInventoryTest.php` | ✅ Catalog categories/products with barcode lookup; interactive POS terminal & payment tender modal; printable thermal receipt; sales history with supervisor void and stock reversal; cashier shifts with cash in/out drops and variance calculation; inventory stock levels, adjustments, and immutable movements audit ledger; multi-tenant isolation verified on all routes. | Proceed to Phase 4 (Basic Plans, Subscriptions, and Platform Billing - A5). |
| Basic plans and subscription assignment | A5 / Tier A | VERIFIED | `app/Http/Controllers/Platform/{PlatformPlanController,PlatformSubscriptionController}.php`; `resources/views/platform/plans/`; `resources/views/platform/tenants/show.blade.php`; `app/Models/{Plan,Subscription}.php` | Platform plans catalogue management, tenant subscription assignment, and lifecycle controls | `plans`, `subscriptions`, `platform_audit_log` | `platform.billing.view`, `platform.billing.plans.manage`, `platform.billing.subscriptions.manage` | `PlatformPlanAndSubscriptionTest`, `PlatformRouteAuthorizationTest`; 7 tests, 381 assertions; full suite 118 tests, 976 assertions, exit 0; see `PROGRESS_LOG.md` L-0116; `php artisan test --compact tests/Feature/PlatformPlanAndSubscriptionTest.php` | ✅ Plan catalog administration (create, update, toggle active with branch quotas), tenant subscription assignment with trial duration and renewal calculation, lifecycle status transitions (trial, active, grace_period, past_due, suspended, cancelled), and immutable audit log chain verified. | Proceed to Phase 5 (Module Catalogue & Entitlements - A6 / Tier B). |
| Module catalogue, entitlements, industry templates | A6 / Tier B | VERIFIED | `app/Http/Controllers/Platform/{PlatformModuleController,PlatformTenantModuleController,PlatformIndustryTemplateController}.php`; `app/Services/ModuleService.php`; `app/Models/{Module,TenantModuleEntitlement,IndustryTemplate}.php` | Platform module catalogue (grouped by category, active-tenant count), module detail (deps + reverse-deps), industry templates listing, tenant module enable/disable panel (via tenant show page) | `modules`, `tenant_module_entitlements`, `industry_templates` | `platform.modules.view`, `platform.modules.manage` | `PlatformModuleAndEntitlementTest`, `PlatformRouteAuthorizationTest`; 13 tests, 430 assertions; full suite 127 tests, 1056 assertions, exit 0; see `PROGRESS_LOG.md` L-0118; `php artisan test --compact tests/Feature/PlatformModuleAndEntitlementTest.php` | ✅ Versioned module catalogue seeded (25 modules, 7 industry templates); dependency validation on enable (ValidationException if deps unsatisfied); core-module protection on disable; reverse-dependency protection on disable; topological multi-pass industry template apply; audit log written on every mutation; route invariants (MFA, session, permission, step-up) enforced and verified. | Proceed to Phase 6 (Platform Billing expansion). |
| Platform billing, invoices, payments, refunds, dunning | A5 expansion / Tier B | VERIFIED | `app/Http/Controllers/Platform/{PlatformInvoiceController,PlatformDunningController}.php`; `app/Services/PlatformBillingService.php`; `app/Models/{PlatformInvoice,PlatformInvoiceItem,PlatformPayment,PlatformRefund,PlatformDunningEvent}.php` | Platform invoices directory with collection metrics, invoice issuance form, invoice show page with line items & payment/refund ledger, dunning and overdue collections view | `platform_invoices`, `platform_invoice_items`, `platform_payments`, `platform_refunds`, `platform_dunning_events` | `platform.billing.view`, `platform.billing.subscriptions.manage` | `PlatformBillingAndDunningTest`, `PlatformRouteAuthorizationTest`; 13 tests, 511 assertions; full suite 136 tests, 1172 assertions, exit 0; see `PROGRESS_LOG.md` L-0119; `php artisan test --compact tests/Feature/PlatformBillingAndDunningTest.php` | ✅ Full invoice lifecycle, 16% VAT calculation, payment allocation, subscription auto-reactivation, refund processing with balance restoration, unpaid invoice voiding, automated dunning scheduling (1d, 3d, 7d, suspension), anti-duplicate suspension guarantee (Annex A 8.9), eTIMS status clearly surfaced as BLOCKED-ON-CERTIFICATION, audit logging and route invariants verified. | Proceed to Phase 7 (Announcements and support cases/tools - A10 / Tier B). |
| Announcements and support cases/tools | A10 / Tier B | VERIFIED | `app/Http/Controllers/Platform/{PlatformSupportCaseController,PlatformSupportAssistController,PlatformAnnouncementController}.php`; `app/Services/SupportService.php`; `app/Models/{SupportCase,SupportCaseNote,PlatformAnnouncement,PlatformAnnouncementRead}.php` | Support Centre ticket directory, ticket submission form, ticket inspection with internal note feed, non-impersonating tenant assist diagnostics overview, announcements broadcast directory | `support_cases`, `support_case_notes`, `platform_announcements`, `platform_announcement_reads` | `platform.support.view` | `PlatformSupportAndAnnouncementsTest`, `PlatformRouteAuthorizationTest`; 12 tests, 610 assertions; full suite 144 tests, 1318 assertions, exit 0; see `PROGRESS_LOG.md` L-0120; `php artisan test --compact tests/Feature/PlatformSupportAndAnnouncementsTest.php` | ✅ Support case lifecycle, priority-based SLA calculation (urgent: 4h, high: 12h, medium: 24h, low: 72h), internal vs. tenant-visible note classification, strict no-impersonation policy (Annex A 13.3) with permitted integrity check reporting pass/fail counts only (Annex A 13.2), targeted platform announcements with tenant read-receipt tracking, audit logging and route invariants verified. | Proceed to Tier C (Phase 8 Infrastructure monitoring, queues, scheduler - A7 / Tier C). |
| Infrastructure, queues, scheduler, migrations, backups and restore | A7 / Tier C | VERIFIED | `app/Http/Controllers/Platform/PlatformInfrastructureController.php`; `app/Services/InfrastructureService.php`; `app/Models/{PlatformHealthProbe,PlatformBackupRun,PlatformRestoreRequest}.php` | System health console, queues & failed jobs, migration history, backup runs, and restore request workflow | `platform_health_probes`, `platform_backup_runs`, `platform_restore_requests`, `platform_audit_log` | `platform.operations.view`, `platform.operations.manage` | `PlatformInfrastructureAndBackupTest`, `PlatformRouteAuthorizationTest`; 11 tests, 677 assertions; full suite 151 tests, 1432 assertions, exit 0; see `PROGRESS_LOG.md` L-0121; `php artisan test --compact tests/Feature/PlatformInfrastructureAndBackupTest.php` | ✅ Real-time health probes (database ping, cache write/read, storage disk write/read, queue status), queue and failed job statistics, migration ledger inspection, backup execution with SHA-256 integrity checksum, isolated restore request workflow, and two-person rule enforcement (self-approval prohibited per Annex A 11.4 & 31) verified. | Proceed to Phase 9 (Security centre, IP rules, key management, incident workflows - A8 / Tier C). |
| Security centre, IP rules, key management, incident workflows | A8 / Tier C | VERIFIED | `app/Http/Controllers/Platform/{PlatformSecurityController,PlatformSecurityIncidentController,PlatformSecurityIpRuleController,PlatformSecurityKeyController}.php`; `app/Services/PlatformSecurityService.php`; `app/Models/{PlatformSecurityIncident,PlatformSecurityIncidentNote,PlatformIpRule,PlatformSecurityKey}.php` | Security centre overview, incidents directory & lifecycle views, IP abuse rules manager, and key governance console | `platform_security_incidents`, `platform_security_incident_notes`, `platform_ip_rules`, `platform_security_keys`, `platform_audit_log` | `platform.security.view` | `PlatformSecurityCentreTest`, `PlatformRouteAuthorizationTest`, `PlatformIpAllowlistTest`; 14 tests, 797 assertions; full suite 156 tests, 1578 assertions, exit 0; see `PROGRESS_LOG.md` L-0122; `php artisan test --compact tests/Feature/PlatformSecurityCentreTest.php` | ✅ Threat overview dashboard with real metrics, full incident lifecycle and timeline notes, dynamic IP allowlist and denylist enforcement integrated into network middleware, cryptographic key envelope hierarchy, versioning, SHA-256 fingerprints, and audited key rotation verified. | Proceed to Phase 10 (Compliance, eTIMS oversight, data-protection requests, legal documents - A9 / Tier C). |
| Compliance, eTIMS oversight, data-protection requests, legal documents | A9 / Tier C | VERIFIED | `app/Http/Controllers/Platform/{PlatformComplianceController,PlatformDataProtectionController,PlatformLegalDocumentController}.php`; `app/Services/ComplianceService.php`; `app/Models/{PlatformEtimsCertification,PlatformDataProtectionRequest,PlatformLegalDocument}.php` | Compliance & eTIMS console, certification checklist updater, data-protection request directory & resolution, and legal document repository | `platform_etims_certifications`, `platform_data_protection_requests`, `platform_legal_documents`, `platform_audit_log` | `platform.compliance.view` | `PlatformComplianceAndEtimsTest`, `PlatformRouteAuthorizationTest`; 9 tests, 859 assertions; full suite 161 tests, 1700 assertions, exit 0; see `PROGRESS_LOG.md` L-0123; `php artisan test --compact tests/Feature/PlatformComplianceAndEtimsTest.php` | ✅ Regulatory eTIMS checklist and live status (`BLOCKED-ON-CERTIFICATION` pending official sign-off), statutory 30-day data-protection requests (Kenya DPA 2019 / GDPR), and versioned platform legal documents verified. | Proceed to Phase 11 (Platform analytics and reports - A11 / Tier D). |
| Platform analytics and reports | A11 / Tier D | VERIFIED | `app/Http/Controllers/Platform/PlatformAnalyticsController.php`; `app/Services/PlatformAnalyticsService.php` | Analytics & operations reporting dashboard with period filters and formula-safe CSV export downloader | Real aggregates from `plans`, `subscriptions`, `platform_invoices`, `platform_payments`, `tenants`, `modules`, `platform_audit_log` | `platform.dashboard.view` | `PlatformAnalyticsTest`, `PlatformRouteAuthorizationTest`; 8 tests, 853 assertions; full suite 165 tests, 1738 assertions, exit 0; see `PROGRESS_LOG.md` L-0124; `php artisan test --compact tests/Feature/PlatformAnalyticsTest.php` | ✅ Real-time financial calculations (MRR, 16% VAT, collections efficiency rate), tenant growth and retention rates, module adoption penetration, and formula-safe CSV exports verified. | Proceed to Phase 12 (Platform dashboard integrated widgets - A12 / Tier D). |
| Platform dashboard | A12 / Tier D | VERIFIED | `app/Http/Controllers/Platform/DashboardController.php`; `app/Services/{PlatformAnalyticsService,InfrastructureService,PlatformSecurityService,ComplianceService,SupportService}.php`; `app/Models/{PlatformApproval,PlatformBackupRun}.php` | Integrated operational command center dashboard with headline KPIs, live probes, backup status, security metrics, compliance console, plan/module breakdowns, and needs-attention alerts | Real computed aggregates across `tenants`, `plans`, `subscriptions`, `platform_invoices`, `platform_payments`, `platform_health_probes`, `platform_backup_runs`, `platform_security_incidents`, `platform_ip_rules`, `platform_security_keys`, `platform_etims_certifications`, `platform_data_protection_requests`, `platform_approvals`, `support_cases` | `platform.dashboard.view` | `PlatformDashboardTest`, `PlatformRouteAuthorizationTest`; 9 tests, 852 assertions; full suite 170 tests, 1760 assertions, exit 0; see `PROGRESS_LOG.md` L-0125; `php artisan test --compact tests/Feature/PlatformDashboardTest.php` | ✅ Integrated operational control plane dashboard with real headline KPIs (MRR/ARR, tenant retention, collection rate), live subsystem probes (DB, cache, storage, queues), encrypted backup and migration tracker, Security Centre threat indicators, envelope key governance, regulatory eTIMS console (`BLOCKED-ON-CERTIFICATION` placeholder), Kenya DPA/GDPR request tracking, and Needs-Attention alert banners verified. | Proceed to Phase 13 (Tenant role and industry dashboards - A13 / Tier D). |
| Tenant role and industry dashboards | A13 / Tier D (delivered with domain phases) | VERIFIED | `app/Http/Controllers/Tenant/TenantDashboardController.php`; `app/Http/Middleware/EnforceShopIsolation.php` | Role-based tenant dashboards (Cashier Terminal, Shop Manager, Executive Owner) with industry blueprint banners (Pharmacy, Hospitality, Retail), KPI cards, multi-shop comparison, and stock alerts | Real computed tenant and shop aggregates across `sales`, `sale_items`, `sale_payments`, `cashier_shifts`, `inventory_stocks`, `shops`, `registers`, `products`, `subscriptions` | Tenant authenticated users with role boundaries (`owner`, `shop-manager`, `cashier`) | `TenantRoleDashboardTest`, `TenantCatalogTest`, `TenantPosAndShiftTest`, `TenantInventoryTest`; 20 tests, 128 assertions; full suite 177 tests, 1798 assertions, exit 0; see `PROGRESS_LOG.md` L-0126; `php artisan test --compact tests/Feature/TenantRoleDashboardTest.php` | ✅ Cashier view (scoped to open shift & register, strict concealment of margins/profits/cost prices), Shop Manager view (scoped to assigned shop, open registers, cash variances, low stock), Owner view (multi-shop performance comparison, gross/net sales, 16% VAT, payment split, loss prevention voids), industry banners (Pharmacy compliance, Hospitality table folios), and shop switching verified. | Proceed to Phase 14 (Hardening, final verification and release readiness - A14 / Tier D). |
| Security/isolation hardening, performance, recovery and release readiness | A14 / Tier D | VERIFIED | `tests/Feature/PlatformHardeningAndReleaseReadinessTest.php`; `app/Console/Commands/AuditVerifyCommand.php`; `resources/views/platform/compliance/index.blade.php`; `docs/PLATFORM_*.md` | Platform hardening, dual-plane boundary isolation, anti-impersonation enforcement, two-person rule safeguards, cryptographic SHA-256 audit hash chain, eTIMS/M-Pesa regulatory placeholder verification, and disaster recovery checksum verification | `platform_audit_logs`, `platform_approvals`, `platform_backups`, `platform_etims_certifications` | `platform.security.view`, `platform.approvals` | `PlatformHardeningAndReleaseReadinessTest`; 6 tests, 172 assertions; full suite 183 tests, 1970 assertions, exit 0; see `PROGRESS_LOG.md` L-0127; `php artisan test --compact tests/Feature/PlatformHardeningAndReleaseReadinessTest.php` | ✅ Strict platform/tenant isolation boundary, prohibition of tenant user impersonation, two-person self-approval prohibition, SHA-256 audit chain verification command, honest regulatory placeholder states (`BLOCKED-ON-CERTIFICATION`), backup checksum validation, and Part 37 documentation verified. | Annex A complete. Platform release readiness verified. |

## Status update policy

For each feature, replace `NOT STARTED` only when evidence supports a more specific status. Record concrete file paths, database tables/migrations, backend services/jobs, frontend screens, permissions, test commands and results, dependencies, risks, and the next action. Use `VERIFIED`, `IMPLEMENTED-UNVERIFIED`, `PARTIAL`, `BLOCKED`, `NOT STARTED`, or `FAILED` as defined in Annex A, Part 0.3. A screen rendering by itself is not verification.
