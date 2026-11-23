# BlePOS — Quality Assurance & Testing Specification

## 1. Quality Policy & Testing Hierarchy

BlePOS enforces rigorous automated testing. No feature is marked `VERIFIED` or considered complete without passing automated test execution.

```
       /\
      /  \    E2E / Browser Acceptance Tests (Playwright / Cypress)
     /----\   Integration & Feature Tests (Laravel Feature Tests, HTTP, DB Transactions)
    /------\  Unit Tests (Financial calculations, tax logic, cryptographic hashes)
```

---

## 2. Testing Layers

### 2.1 Unit Tests (`tests/Unit`)
- Precise mathematical assertions: Multi-currency conversions, VAT calculations (16% tax inclusive vs exclusive), bulk discount algorithms, and interest proration.
- Cryptographic checks: TOTP secret generation and validation (`PlatformTotpTest`), audit hash chain computation.
- Strict floating-point avoidance: All financial assertions verify exact string or integer cent representations.

### 2.2 Feature & Integration Tests (`tests/Feature`)
- **Platform Identity & Security**: Login throttles, MFA enrollment, recovery codes, step-up authentication, and session cookie separation (`PlatformLoginTest`, `PlatformMfaTest`, `PlatformStepUpTest`, `PlatformSessionCookieTest`).
- **Authorization & RBAC**: Granular permission checks, built-in role boundary enforcement, and direct-action denial tests with route middleware bypassed (`PlatformRoleBoundaryTest`, `PlatformRouteAuthorizationTest`).
- **Two-Person Approvals**: Self-approval prevention, role constraints, and expiry (`PlatformApprovalWorkflowTest`, `PlatformMfaResetApprovalTest`).
- **Audit Ledger Integrity**: Tamper-detection, database trigger protection, and formula-safe CSV export (`PlatformAuditIntegrityTest`, `PlatformAuditExportTest`).
- **Tenant Isolation**: Tests asserting that Tenant A cannot query or mutate Tenant B records, and Cashier A cannot view Shop B registers.

---

## 3. Execution Commands & Test Harness

```bash
# Run the entire test suite with compact agent-optimized reporting
php artisan test --compact

# Run a specific feature test
php artisan test --compact tests/Feature/PlatformRoleBoundaryTest.php

# Run code style formatting (Laravel Pint)
vendor/bin/pint --format agent

# Run progress ledger integrity validation
./scripts/progress-check
```
