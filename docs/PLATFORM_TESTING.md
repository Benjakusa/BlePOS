# BlePOS — Platform Testing & Quality Assurance

## 1. Overview

BlePOS adheres to rigorous testing standards. All features must be accompanied by comprehensive automated feature and unit tests before being marked `VERIFIED`.

## 2. Test Suite Architecture

- **Framework**: PHPUnit / Laravel Testing Suite.
- **Test Database**: SQLite in-memory for deterministic, lightning-fast test execution.
- **Isolation Guarantee**: Every test executes within database transactions with refreshed state.

## 3. Suite Statistics (End of Phase 14 / Annex A Completion)

- **Total Tests**: 183 feature and unit tests.
- **Total Assertions**: 1,970 assertions.
- **Pass Rate**: 100% (0 failures, 0 errors).
- **Execution Time**: ~20 seconds across full suite.

## 4. Core Feature Test Suites

| Test File | Focus Area |
|---|---|
| `PlatformHardeningAndReleaseReadinessTest.php` | Phase 14 release readiness, isolation boundaries, audit tamper detection, placeholder states |
| `PlatformDashboardTest.php` | Phase 12 platform console dashboard counters, KPIs, and security widgets |
| `TenantRoleDashboardTest.php` | Phase 13 cashier, manager, and owner shop-scoped dashboard views |
| `PlatformAnalyticsTest.php` | Phase 11 ARR, MRR, industry distribution, and churn analytics |
| `ShopIsolationTest.php` | Multi-shop scoping and cross-branch data containment |
| `PlatformAuditTest.php` | Cryptographic SHA-256 hash chaining on platform audit logs |
| `PlatformMfaTest.php` | TOTP MFA verification and step-up auth barriers |

## 5. Running Tests

```bash
# Run entire test suite
php artisan test --compact

# Run specific feature test
php artisan test --compact tests/Feature/PlatformHardeningAndReleaseReadinessTest.php
```
