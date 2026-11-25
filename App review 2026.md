# BlePOS Application Review

## Review — 2026-11-25 — Functional, isolation, RBAC, industry and integrity audit

### Executive Summary

- The current worktree passes the requested PHP suite (291 tests, 2,752 assertions), Pint, and Vite build. Compared with the ledger's cited 243 / 2,491 baseline, this is +48 tests and +261 assertions with zero reported failures. The ledger's latest recorded run is itself 284 / 2,686, so this run is +7 / +66 versus that later ledger entry.
- Severity count: **P0: 0; P1: 2; P2: 4; P3: 1.** These are static/code-evidence findings; exhaustive route-by-route IDOR replay and a disposable-database migration/provisioning drill were not completed.
- Top business-owner risks:
  1. Provisioning grants the cashier `pos.checkout`, but the POS routes/controllers authorize `pos.sale.create`; newly provisioned cashiers can be denied checkout (`app/Services/ProvisioningEngine.php:256-305`, `routes/web.php:472-478`, `PosController.php:31,72`).
  2. `platform:audit-verify` verifies only `platform_audit_logs`; the tenant-side financial activity recorder is not in that SHA-256 chain (`app/Services/PlatformAuditVerifier.php:5-16`, `app/Http/Middleware/RecordTenantActivity.php`).
  3. Sale line and payment child models, among others, do not apply tenant/shop traits themselves; their query safety depends on reaching them through correctly scoped parents (`app/Models/SaleItem.php`, `SalePayment.php`).
  4. Shift open/close/cash movement updates lack an explicit transaction and row lock in the controller; concurrent checkout/close behavior needs a focused race test (`app/Http/Controllers/Tenant/CashierShiftController.php:78-124,146-204`).
  5. The requested `migrate:fresh --seed` was not run because the configured database was not proven disposable; provisioning's complete 30-step idempotency/failure-recovery behavior remains unverified by this audit.
- Overall recommendation: **PASS-WITH-FIXES**. Core automated regression and static authorization boundaries pass their existing tests, but the cashier permission mismatch and financial audit-chain scope merit remediation before relying on the full business workflow.

### 1. Baseline Verification

| Command | Result | Delta vs. ledger |
|---|---|---|
| `php artisan test --compact` | PASS — 291 tests, 2,752 assertions, 0 failures; 36.385 s | +48 / +261 versus 243 / 2,491; +7 / +66 versus latest ledger row 284 / 2,686 |
| `vendor/bin/pint --test` | PASS — `{"tool":"pint","result":"passed"}` | Matches green ledger status |
| `npm run build` | PASS — Vite 8.3.8; built assets, exit 0 | Matches green ledger status |
| `php artisan route:list --except-vendor` | PASS — 331 routes displayed | Inventory only; does not itself test route responses |
| `php artisan migrate:fresh --seed 2>&1 \| tail -50` | **NOT RUN** — database connection was not verified as disposable | No migration result claimed |

The progress document reports 284 tests / 2,686 assertions as its latest suite row and an older app-review baseline of 243 / 2,491. No regression is indicated by the run captured above. Build output wrote generated assets under `public/build`; existing dirty files were present before this review and were preserved.

### 2. Functionality & Flows

| Flow | Entry | Guards | State transitions | Ledger claim matches? | Notes / bugs |
|---|---|---|---|---|---|
| Platform console | `platform-console/login`; dashboard and feature routes in `routes/web.php:74-409` | Separate `platform` guard, `platform.mfa`, `platform.session`, route permissions; mutation routes add `platform.step-up` | Password accepted → MFA → session → authorized action; approvals support two-person workflow | Partially evidenced by `PlatformRoleBoundaryTest`, `PlatformHardeningAndReleaseReadinessTest`; all console flows not replayed | Platform screens do not expose an impersonation route in the 331-route listing; support assist is separate and integrity-check only. Full PII/data-flow review remains unverified. |
| Tenant POS | `/tenant/pos/checkout`, `/tenant/pos/process`, receipt, sales and void routes (`routes/web.php:472-478`) | Tenant auth/MFA/context/isolation group at `routes/web.php:426`; permission checks in route and `PosController`; shift/user/shop validation | Shift open → sale transaction → payment validation → stock decrement → receipt; void creates reversal and accounting entry via `SaleService::voidSale` | Partial: `TenantPosAndShiftTest`, `CorePosAndInventoryTest`, `PharmacyCheckoutSafetyTest`; no live M-Pesa provider test | **P1** cashier grant name mismatch: provisioning step 16 adds `pos.checkout`, while POS checks `pos.sale.create` and sales uses `pos.sales.view`. |
| Inventory | `/tenant/inventory`, adjust/receive/movement (`routes/web.php:488-494`) | Tenant-wide middleware, `inventory.*` permissions, validated fields | Intake/adjustment calls `InventoryService`; sale and void paths record stock movements | Partial: tests cover stock changes and tenant/shop boundaries | Adjust requires a reason and service records ledger movement; direct full lifecycle reversal was not replayed here. |
| Pharmacy | `/tenant/pharmacy`, batch and prescription routes; checkout guard in `SaleService` | Pharmacy industry route gate (`routes/web.php:466-470`), tenant permissions, batch/prescription validation; controlled drug path checks pharmacist identity/permission (`PharmacyService.php`) | Batch intake → FEFO/expiry/quarantine validation → checkout → dispensing record | Ledger says pharmacy checkout controls verified; `PharmacyCheckoutSafetyTest` exists and passed in full suite | Pharmacist licensed credential validation is explicitly a ledger gap; M-Pesa provider is out of scope. |
| Restaurant / hotel | Restaurant tables guarded by `tenant.industry:restaurant` (`routes/web.php:507-513`); hotel template enables hospitality module | Industry/module routes and tenant permission middleware | Restaurant table status and POS sale; hotel rooms/reusable-stock flow is not equivalent to a full folio workflow | Restaurant subset verified by ledger; hotel implemented subset only | No evidence here of full table-service order queue or hotel folio end-to-end acceptance. Do not infer those features. |
| Provisioning | Platform tenant registration and provisioning routes; `ProvisioningEngine` | Platform permission + step-up on mutation; engine step state recorded | 30 ordered steps, retry path for failed run | **UNVERIFIED in this review** | No disposable tenant engine replay was performed; idempotency and failure recovery rely on existing tests/docs, not a fresh drill. |

### 3. Tenant Isolation

#### Model scoping audit

`app/Models` contains 106 PHP model files; 63 reference `BelongsToTenant` and 49 reference `BelongsToShop`. These counts include platform/global models and are not a claim that all remaining 43/57 are tenant entities. A targeted scan found these tenant-related child/configuration models without either trait:

| Model | BelongsToTenant | BelongsToShop | Notes |
|---|---:|---:|---|
| `SaleItem` | No | No | Child record; normally reached through scoped `Sale::items`; direct model query is unscoped. |
| `SalePayment` | No | No | Child record; normally reached through scoped `Sale::payments`; direct model query is unscoped. |
| `PharmacyPrescriptionItem` | No | No | Child of prescription; parent relationship should remain the only access path. |
| `JournalEntryLine` | No | No | Child of journal; accounting parent must be scoped. |
| `SupportCase` / `SupportCaseNote` | No | No | Shared platform/tenant support models. Tenant controller explicitly filters cases by tenant and hides internal notes; platform support intentionally queries across tenants. |
| `MpesaCredential` / `MpesaCredentialAudit` | No | No | Multi-owner (`owner_type` / `owner_id`) records; controllers/resolver manually constrain owner and use audited `withoutGlobalScopes()`. A global trait is not present. |

The complete model-file scan also found these no-trait models: `IndustryTemplate`, `JournalEntryLine`, `Module`, `MpesaCredential`, `MpesaCredentialAudit`, `PharmacyPrescriptionItem`, `Plan`, `PlatformAnnouncement`, `PlatformAnnouncementRead`, `PlatformApproval`, `PlatformAuditLog`, `PlatformBackupRun`, `PlatformDataProtectionRequest`, `PlatformDunningEvent`, `PlatformEtimsCertification`, `PlatformHealthProbe`, `PlatformInvitation`, `PlatformInvoice`, `PlatformInvoiceItem`, `PlatformIpRule`, `PlatformLegalDocument`, `PlatformPayment`, `PlatformPermission`, `PlatformRefund`, `PlatformRestoreRequest`, `PlatformRole`, `PlatformSecurityEvent`, `PlatformSecurityIncident`, `PlatformSecurityIncidentNote`, `PlatformSecurityKey`, `PlatformSession`, `PlatformUser`, `ProvisioningStep`, `SaleItem`, `SalePayment`, `SupportCase`, `SupportCaseNote`, `Tenant`, `TenantDomain`, and `TenantPermission`. `SupportCase` tenant actions include explicit tenant predicates in `TenantSupportCaseController.php:20,55,65`; support notes are loaded through that scoped case and exclude `is_internal` at line 56. Platform/global roots are not expected to carry tenant traits. This list records the scan result; each special cross-plane/child model still requires caller-level ownership constraints.

Other no-trait models found by the scan are platform/global records or tenant/shop roots (including `Tenant`, `TenantDomain`, and platform audit/control-plane models). No evidence of a concrete cross-tenant response from the listed child models was obtained. **P2 defense-in-depth finding:** add explicit scoped relationships/query helpers and tests proving direct access cannot escape a scoped parent.

#### Route isolation audit

The authenticated tenant route group globally applies `web`, `auth`, `tenant.resolve`, `tenant.mfa`, `tenant.isolate`, `shop.isolate`, and `tenant.activity` (`routes/web.php:426`). Tenant route model binding and controller checks are present in sampled sale/shift paths. `TenantIsolationTest` verifies cross-tenant denial; `ShopIsolationTest` verifies an unassigned-shop 403 and a cross-tenant-shop 404; platform hardening tests verify the platform/tenant boundary.

| Route set | Middleware present | IDOR evidence/result | Severity |
|---|---|---|---|
| Tenant core routes (all routes under `/tenant`) | Shared context, tenant and shop middleware; per-route permission on protected operations | Existing isolation tests cover representative tenant/shop binding. **All 331 routes were not individually replayed with Tenant B IDs.** | Unverified full coverage |
| Restaurant tables | Shared middleware + `tenant.industry:restaurant`; permission on each action | Static route gate confirms industry-limited exposure; no non-restaurant direct request executed in this audit | Unverified live response |
| Pharmacy routes | Shared middleware + `tenant.industry:pharmacy`; permission per operation | Static route gate; checkout-specific safety tests passed in suite | Representative only |
| Gym, printing, agrovet, garage, fleet, parcel routes | Shared middleware + `module.enabled:<slug>` and route permissions | `EnsureModuleEnabledTest` covers enabled/disabled representative route behavior; no per-route IDOR fuzz/replay | Representative only |
| Platform console | Separate platform guard, MFA/session, permissions and step-up on writes | `PlatformRoleBoundaryTest` proves controller permission denial when middleware is removed for selected platform mutations; platform/tenant boundary test exists | Representative only |

#### `withoutGlobalScope(s)` audit

All occurrences were enumerated with `rg -n 'withoutGlobalScope|withoutGlobalScopes' app`. Uses found in `Product::availableStockForShop`, callback credential resolution, platform/tenant M-Pesa credential controllers, `ResolveTenantContext`, `EnforceShopIsolation`, inventory adjustment, sale idempotency lookup, reusable-stock release command, subscription selection, parcel office routing/code allocation, workspace notices, tenant login, and scoped test fixtures. Manual predicates are normally adjacent (`tenant_id`, `shop_id`, or owner IDs). Each use should retain its explicit ownership predicate; this review did not identify a confirmed cross-tenant return, but did not replay every query.

### 4. Role-Based Access

#### Capability matrix

Legend: **A** = permitted by role/permission design; **D** = denied by the relevant permission boundary; **C** = conditional on an explicit permission; **U** = not established for every screen/action by test evidence. Tenant screen columns group all registered screens into operational areas. Platform screen columns group console routes in `routes/web.php`.

| Role | Tenant: POS / receipt / sales | Catalog / inventory | Reports / finance | Staff / tenant settings | Industry screens | Platform dashboard / tenant data | Platform billing / provisioning | Platform security / audit | Platform support / compliance / infra |
|---|---|---|---|---|---|---|---|---|---|
| Owner | A | A | A | A | A (within tenant) | D | D | D | D |
| Shop Manager | C | C | C | D unless granted | C, branch constrained | D | D | D | D |
| Cashier | C (see P1 mismatch below) | D | D | D | D unless granted | D | D | D | D |
| Pharmacist | C | C | C | D unless granted | Pharmacy C | D | D | D | D |
| Accountant | C | D | A when granted | D | D | D | D | D | D |
| Super Administrator | D | D | D | D | D | A (control plane; no tenant impersonation route) | A | A | A |
| Platform Operations Administrator | D | D | D | D | D | C | D | D | C (infrastructure) |
| Billing Administrator | D | D | D | D | D | C | A | D | D |
| Support Agent | D | D | D | D | D | C (tenant metadata) | D | D | C (support) |
| Compliance / eTIMS Officer | D | D | D | D | D | C | D | D | C (compliance) |
| Platform Auditor | D | D | D | D | D | C (metadata/reports) | C (billing view) | C (read-only audit/security view) | D |
| Security Officer | D | D | D | D | D | C | D | A (bounded security actions) | D |

Tenant roles `owner`, `shop-manager`, `cashier`, and `accountant` are seeded in `ProvisioningEngine.php:241-253`; `pharmacist` and `cashier` are defined in `PharmacyDemoSeeder.php:148-164`. The seven platform built-ins and their permission sets are explicit in `PlatformRoleSeeder.php`. For many tenant roles, actual grants are tenant-specific; the matrix therefore reflects intended/permission-gated access, not a claim that each unseeded combination was exercised.

**Cashier POS-only:** the role boundary is enforced by permission middleware and a dedicated cashier dashboard (`TenantDashboardController.php:84-118`, `EnsureTenantPermission.php:16-33`). Provisioning's standard cashier receives `pos.checkout` only (`ProvisioningEngine.php:302-305`), while routes and controller demand `pos.sale.create` / `pos.sales.view` (`routes/web.php:472-478`, `PosController.php:31,72,199`). Thus the POS-only boundary exists, but functional cashier access from standard provisioning is **not verified and appears mismatched** (P1). The restaurant demo role uses a different set of grants and does not prove the generic provisioning path.

**Shop Manager branch scope:** `ShopIsolationTest::test_cashier_can_access_assigned_shop_and_is_denied_access_to_unassigned_shop` and `test_multi_shop_manager_can_access_all_shops_within_tenant` passed in the full suite. Those are representative middleware checks; a full controller route matrix remains unverified.

**Owner scope:** owner permission bypass is tenant-local in `EnsureTenantPermission.php:24-30`; tenant context guards and `TenantIsolationTest`/platform boundary tests passed. No route for impersonating tenant users appears in the current route inventory. Support assist has separate routes; the complete response payload was not audited here.

**Direct-request denial / middleware removed:** `PlatformRoleBoundaryTest` removes `EnsurePlatformPermission` and confirms selected controller actions still return 403, including role assignment/removal, password reset, status, approval cancel, and session revoke. Equivalent middleware-removal tests for every tenant mutation controller were not run; full tenant controller-level authorization coverage is **UNVERIFIED**.

**Step-up:** platform console routes are nested under MFA/session at `routes/web.php:93`; sampled mutations add `platform.step-up` (tenant create, plan change, provisioning retry, compliance writes, etc.). `PlatformRoleBoundaryTest` exercises pre-established MFA/step-up session markers, not the absence of each marker across every write route. Full step-up route coverage remains unverified.

### 5. Industry / Module Content Correctness

The module catalogue and templates are defined in `database/seeders/ModuleCatalogueSeeder.php:228-500`. Industry routes have server-side industry or entitlement middleware; sidebars are not the only gate. Restaurant tables are inside `tenant.industry:restaurant` (`routes/web.php:507-513`); pharmacy inside `tenant.industry:pharmacy` (`466-470`); five verticals and parcel routes use `module.enabled` (`531-650`). `EnsureModuleEnabled` returns 403 if there is neither an enabled entitlement nor a matching industry slug (`app/Http/Middleware/EnsureModuleEnabled.php:20-30`). `EnsureModuleEnabledTest` passed in the baseline suite.

Matrix cells show whether the template includes that domain's module family. ✅ = included; — = absent from template; ⚠️ = route/content claim needs a direct tenant response test. Base POS is included across these templates and omitted from this abbreviated vertical-family table.

| Industry template | Pharmacy | Restaurant / tables | Hospitality / hotel | Fuels | Gym | Printing | Agrovet | Garage | Fleet | Parcel | Cross-industry leak found |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---|
| retail | — | — | — | — | — | — | — | — | — | — | No leak shown by template list; direct route tests unverified |
| pharmacy | ✅ | — | — | — | — | — | — | — | — | — | No table module in template; direct table request unverified |
| restaurant | — | ✅ | — | — | — | — | — | — | — | — | No pharmacy module in template; table route industry-gated |
| hotel | — | — | ✅ | — | — | — | — | — | — | — | Rooms/reusable-stock subset, not proof of complete folios |
| wholesale | — | — | — | — | — | — | — | — | — | — | Warehouse/transfer modules in template |
| salon | — | — | — | — | — | — | — | — | — | — | No fuel fields/routes identified in template |
| fuel-station | — | — | — | ✅ | — | — | — | — | — | — | Fuel module only appears in fuel template |
| gym-fitness | — | — | — | — | ✅ | — | — | — | — | — | Gym route family entitlement-gated |
| printing-branding | — | — | — | — | — | ✅ | — | — | — | — | Printing route family entitlement-gated |
| agrovet | — | — | — | — | — | — | ✅ | — | — | — | Agrovet route family entitlement-gated |
| auto-garage | — | — | — | — | — | — | — | ✅ | — | — | Garage route family entitlement-gated |
| car-hire-fleet | — | — | — | — | — | — | — | — | ✅ | — | Fleet route family entitlement-gated |
| parcel-delivery | — | — | — | — | — | — | — | — | — | ✅ | Parcel route family entitlement-gated |

Templates for the original seven industries use `mod-pharmacy`, `mod-restaurant`, `mod-hospitality`, and `mod-fuels` names; the new five modules use distinct slugs. Table/nav/widget rendering across every seeded industry and disabled entitlement was not browser-tested. No cross-industry leak was confirmed from template lists or route gates. **Direct-route non-entitled result:** representative module middleware test passes; per-module route live probes are unverified.

### 6. Data Integrity for Business Owners

#### Integrity matrix

| Entity | Delete kind | Who can | Audit? | Reversible? | Risk | Recommendation |
|---|---|---|---|---|---|---|
| Product | DELETE route is registered, but `ProductController` has no `destroy` method; `Product` has no `SoftDeletes` trait | Route middleware: `catalog.products.manage` | No successful delete/audit path exists | No supported delete or restore path | P2 | Implement an explicit archive/soft-delete action with audit and foreign-key safety, or remove the DELETE route |
| Category | Hard delete after refusing deletion with products or child categories | `catalog.categories.manage` | Tenant activity middleware observes successful mutation; no tenant hash chain | No restore path | P2 | Prefer archive/soft-delete if category history should be retained; test audit and references |
| Completed sale / receipt | Void/reversal; sale retained | `pos.sale.void` | Sale/stock/accounting reversal records; no tenant hash chain | Compensating void path | P1 | Keep immutable original sale and hash-chain tenant financial events |
| Sale items / payment lines | No standalone route delete found | Parent sale flow | Referenced by sale; no independently verified immutability constraint | Not verified against direct model writes | P2 | Add DB/model immutability guards and mutation-denial tests |
| Stock / movement | Adjustments are movement records; no inventory delete route found | `inventory.adjust` / `inventory.receive` | Movement ledger and reason captured in service/controller | Reversal should be a compensating movement | P2 | Enforce mandatory adjustment reason on every writer and test actor audit |
| Cashier shift | No delete route; close transition persists actual/expected/variance | `shifts.manage` | Tenant activity entry; no tenant financial chain | Closed state blocks controller close/cash-move paths | P2 | Row-lock shift and test simultaneous close/sale; disallow closed-shift edits |
| Pharmacy batch / dispensing | No delete route found; stock consumption is recorded | Inventory receive / pharmacy permission routes | Dispensing record plus inventory events | Corrections should be stock reversal | P2 | Verify batch correction/void policy with audit trail |
| Tenant staff | Tenant user resource excludes destroy (`routes/web.php:451-454`) | `users.manage` for create/update | Activity middleware | Update only; owner deletion is not exposed by route | Lower risk | Add explicit owner-protection regression test |
| Plans / subscription / platform billing | Plan update/toggle; no plan delete route; invoices can be void/refund | Platform billing permissions + step-up on selected mutations | `PlatformAuditRecorder` | Invoice void/refund is a compensating record | P2 | Verify every billing mutation's step-up and two-person policy |

**Delete/edit audit:** route inventory has product DELETE and restaurant table DELETE, but no tenant sale, shift, batch, or staff DELETE route in the sampled core route group; staff resource explicitly excludes destroy. POS sale void validates a reason and calls a reversal service (`PosController.php:226-240`, `SaleService.php:298-325`). No completed-sale update route was found in `routes/web.php`. This static check does not prove database-level immutability.

**Shift tampering:** no update route exists for a closed shift; close and cash movement reject a non-open shift (`CashierShiftController.php:146-200`). Open shift close is permission-gated, but the controller does not explicitly bind the action to the shift owner or active shop beyond tenant ID. Whether all authorized shop managers should be able to close another cashier's shift is a policy decision; test direct cross-branch requests before release.

**Stock manipulation:** `InventoryController::adjust` validates delta/reason and calls `InventoryService` (routes require `inventory.adjust`); all alternate writers were not exhaustively audited. No finding that a staff member can adjust without reason was confirmed.

**Backdating:** sampled sale and shift create/close paths use server `now()`; user-supplied created/opened/closed timestamps are not accepted in those validators. Other industry-specific dates/bookings were not exhaustively checked.

**Audit chain coverage — P1:** `PlatformAuditVerifyCommand` calls `PlatformAuditVerifier`, which scans only `PlatformAuditLog` and computes SHA-256 links (`app/Console/Commands/PlatformAuditVerifyCommand.php:10-24`, `app/Services/PlatformAuditVerifier.php:5-44`). Tenant activity uses a separate request recorder and stores no submitted values, but is not included in this verified hash chain. Financial mutations therefore lack the requested tamper-evident verification. Existing audit command test proves platform-chain verification only.

**Two-person rule:** platform approvals exist and route handlers require the approval permission; evidence is in `PlatformApprovalController` and `PlatformRoleBoundaryTest`. Equivalent tenant approval for unusually large write-offs, price changes, or refunds was not identified; recommend as P2 policy hardening, not as proof of exploitable existing route.

**Owner protection:** staff resource has no destroy action; role changes require `users.manage`. Owner role bypass is in `EnsureTenantPermission`. No test specifically attempted staff deletion/demotion of the owner; mark **UNVERIFIED — add owner-protection feature test**. Subscription self-service is routed under `users.manage` in `routes/web.php:515-528`; platform subscription operations have billing permissions.

### 7. Bugs & Loopholes

| ID | Severity | Area | File:line | Description | Repro | Impact | Suggested fix |
|---|---|---|---|---|---|---|---|
| R-01 | P1 | RBAC / POS | `app/Services/ProvisioningEngine.php:256-305`; `routes/web.php:472-478`; `app/Http/Controllers/Tenant/PosController.php:31,72,199` | Generic provisioning grants `pos.checkout`; active POS checks `pos.sale.create` and `pos.sales.view`. No alias/mapping was found in the standard step-16 permission list. | Provision tenant with standard engine; log in as its cashier; request checkout and process route. Expected permission denies until grants are aligned. Not executed against disposable provisioned tenant. | Cashier is unable to perform core work; alternate demo seed permissions may mask it. | Standardize permission slugs in provisioning, route/controller checks, and role tests; add end-to-end test for a freshly provisioned cashier. |
| R-02 | P1 | Financial audit integrity | `app/Services/PlatformAuditVerifier.php:5-16`; `app/Http/Middleware/RecordTenantActivity.php` | SHA-256 verifier covers platform audit log only; successful tenant financial mutations are not in that chain. | Record POS sale/void or inventory adjustment, then run `php artisan platform:audit-verify`; verifier scans platform log only. | A privileged DB writer can alter tenant financial activity without this command detecting it. | Include tenant financial audit events in an append-only, verifiable chain and add tamper test spanning sale, void, stock and shift events. |
| R-03 | P2 | Tenant isolation defense-in-depth | `app/Models/SaleItem.php`; `SalePayment.php`; `PharmacyPrescriptionItem.php`; `JournalEntryLine.php`; `SupportCase.php`; `SupportCaseNote.php` | Tenant child models have no tenant/shop scopes of their own; direct `Model::query()` is unscoped unless caller constrains through the parent. Support controllers explicitly scope case IDs by tenant, but the model itself does not. No concrete leaking route found. | From a tenant request or future controller, query a child row by guessed ID without parent relationship. Not executed; no direct route currently identified. | Future direct use can expose or mutate cross-tenant child/support data. | Add tenant ownership query helpers or require scoped parent relationships; test guessed foreign IDs, including internal support-note visibility. |
| R-04 | P2 | Concurrency / shift integrity | `app/Http/Controllers/Tenant/CashierShiftController.php:78-124,146-204`; `app/Services/SaleService.php:42-52,279+` | Shift open, cash movement and close are not wrapped in a row-locked transaction in the controller. The POS sale transaction is separate; a concurrent close can race a sale's shift totals. | Concurrently submit close and sale for the same open shift; inspect persisted totals/closed state. Not run in this review. | Incorrect expected cash, omitted sale totals, or a sale attached to a closed shift. | Lock shift row and serialize sale/close mutations in one transaction; add concurrency test on supported DB engines. |
| R-05 | P2 | Precision audit | `app/Http/Controllers/Tenant/CashierShiftController.php:113,119,159,186`; `PosController.php:107,163`; `SaleService.php:116` | Financial inputs/quantities are converted through PHP float before decimal formatting or comparisons. Settlement totals use BCMath later, but conversion can lose precision for high precision values. | Submit boundary values with more decimal places than currency precision to shift/checkout endpoints and compare persisted values. Not run. | Rounding discrepancies in cashier variance/settlement at edge values. | Validate currency values to two decimal places and normalize with decimal-string arithmetic before arithmetic; retain floats only for non-money quantities where intended. |
| R-06 | P3 | Verification coverage | `routes/web.php` (331 routes); `tests/Feature/TenantIsolationTest.php`; `ShopIsolationTest.php` | Existing tests cover representative isolation routes, not every direct-object route, module route, or controller with middleware removed. This is a coverage gap, not a confirmed IDOR. | Enumerate each route with a bound object and exercise using a second tenant/shop and reduced middleware. Not completed. | Unseen IDOR could remain undetected. | Generate parameterized route authorization tests from route metadata and add cross-tenant fixtures for every model-bound route group. |
| R-07 | P2 | Product delete flow | `routes/web.php:463-464`; `app/Http/Controllers/Tenant/ProductController.php` (no `destroy` method); `app/Models/Product.php:11-15` | `Route::resource('products', ...)` registers DELETE, but controller has no destroy handler and model has no soft-delete behavior. | Authenticated user with `catalog.products.manage` sends DELETE to `/tenant/products/{product}`; static dispatch target is absent. Request not executed. | Product removal is broken and may produce a server error; staff cannot safely retire an obsolete product through that route. | Add a tested archive/soft-delete handler preserving sale history and audit, or remove the route until implemented. |

No confirmed mass-assignment endpoint was identified in the scans: no `$guarded = []` or request `all()` feed into model create/update was found by the targeted search. That is not a proof of safety for every `forceFill`/query-builder operation. No secret leak in logs/responses was confirmed; M-Pesa credentials are hidden/masked in model/UI paths (`MpesaCredential.php`, `resources/views/platform/mpesa/*`).

### 8. Cross-Cutting Security

- **MFA / step-up:** platform console feature routes are nested under `platform.mfa` and `platform.session`; mutation routes visibly apply step-up in samples. Full route-by-route exception audit is unverified. Tenant routes apply `tenant.mfa` globally (`routes/web.php:426`).
- **CSRF:** web routes are grouped through Laravel `web`; the M-Pesa callback is declared at `routes/web.php:70` as `POST /api/mpesa/callback/{token}`. Middleware exception list was not located/audited in this pass. **UNVERIFIED — inspect bootstrap CSRF exceptions and verify callback token/signature.**
- **Rate limiting:** platform/tenant login and password reset routes use named throttles; M-Pesa credential tests use `throttle:5,1`; tenant support writes are throttled (`routes/web.php:78-82,415,438-440,523`). Payment route coverage needs provider-specific review.
- **Uploads:** product image upload and validation are covered by browser acceptance per ledger, but storage path isolation, MIME sniffing and size limits were not exhaustively traced here.
- **Backup/restore:** platform-only route group includes backup trigger and restore request/approval routes in route list; `docs/PLATFORM_BACKUPS_RECOVERY.md` documents SQLite-only isolated restore and database dump tooling limitations. Tenant-triggered restore route was not found. Production-engine restore drill remains an operator task per ledger.
- **Impersonation / PII:** no impersonation route appears in the 331-route inventory; support assist has a limited integrity-check route in `routes/web.php`/platform support controller. Complete PII payload review remains unverified. The no-impersonation policy is not evidence by itself.
- **Time zones:** tested flows use Laravel `now()` and database timestamps; tenant-specific timezone conversion and day/month/year reporting boundaries need focused tests. No confirmed timezone bug found.
- **Notifications:** ledger records tenant activity reporting but broader outbound reminder delivery is absent for some industry modules (L-0129); do not treat those reminders as delivered.

### 9. Recommendations (prioritized)

#### P0

- None confirmed by this review.

#### P1

- Align generic cashier permission grants and POS route/controller permission names in `ProvisioningEngine`, `routes/web.php`, and `PosController`; add a fresh-provision cashier checkout test.
- Extend tamper-evident audit verification to tenant financial events (sale, void/refund, payment, stock movement, shift reconciliation) and add a mutation/tamper regression test.

#### P2

- Add a concurrency-safe transaction/row lock for shift state and totals in `CashierShiftController` and sale/shift service; test concurrent sale and close.
- Add tenant ownership protection for `SaleItem`, `SalePayment`, `PharmacyPrescriptionItem`, and `JournalEntryLine` direct queries; test foreign-parent IDs.
- Implement or remove the registered product DELETE route; preserve sales/stock history and audit any archive action.
- Add explicit owner undeletable/untamperable tests and define two-person approval thresholds for tenant high-risk write-offs/refunds.
- Normalize monetary input as two-decimal strings before comparisons/conversion in shift and POS controllers; test precision boundaries.
- Complete route-level disabled-module probes and cashier/shop-manager direct-request matrix.

#### P3

- Build automated route metadata coverage that tests cross-tenant/shop IDs for all model-bound tenant routes and role denials when permission middleware is bypassed.
- Add upload MIME/path isolation, CSRF exception, date-boundary/timezone and reminder-delivery checks to the release suite.

### 10. Fix Plan (follow-up session)

1. **Permission contract:** choose canonical POS grants; update standard provisioning and seed role grant mapping; acceptance: fresh tenant cashier can load checkout, complete cash sale and view own receipt while catalog, inventory, reports and staff screens remain denied.
2. **Tenant financial audit chain:** design append-only tenant event hash structure; acceptance: sale, void, payment, stock and shift events verify, and changing/deleting a stored event makes the verifier fail.
3. **Shift integrity:** row-lock open shift on sale/close/cash-movement; acceptance: parallel sale/close cannot lose totals or mutate a closed shift; DB-engine concurrency test included.
4. **Child-row scope:** scope tenant child models through tenant/shop or scoped parent access; acceptance: tenant A cannot query/update tenant B's sale item, sale payment, prescription item or journal line by ID.
5. **Owner protection and approval policy:** explicit owner deletion/demotion tests; acceptance: staff cannot delete or demote owner, and agreed high-risk tenant operations require second approver.
6. **Precision / broad coverage:** normalize monetary strings and add route-matrix test generation; acceptance: money boundary cases are deterministic and every model-bound route has representative foreign-tenant and unassigned-shop denial.
7. **Operational drill:** run all 30 provisioning steps on an isolated disposable DB/tenant; acceptance: second run is idempotent and injected step failure resumes safely without duplicate resources.

### Appendix A — Commands run (with output excerpts)

```text
$ php artisan test --compact
{"tool":"phpunit","result":"passed","tests":291,"passed":291,"assertions":2752,"duration_ms":36385}

$ vendor/bin/pint --test
{"tool":"pint","result":"passed"}

$ npm run build
vite v8.3.8 building client environment for production...
✓ built in 1.40s

$ php artisan route:list --except-vendor
Showing [331] routes

$ php artisan migrate:fresh --seed 2>&1 | tail -50
NOT RUN: configured database was not established as disposable.
```

Additional read-only commands: `git status --short`; `rg` scans for model traits, `withoutGlobalScopes`, money float casts, permissions, routes and industry templates; `sed`/`nl` reads of controllers, middleware, tests and seeders. Initial status showed pre-existing modified/untracked files in demo parcel/legal/dashboard work; they were not edited by this review. Build generated `public/build` assets.

### Appendix B — Evidence file index

- Ledger and status: `docs/PROGRESS.md`, `docs/PROGRESS_LOG.md`, `docs/ANNEX_A.md`, `docs/INDUSTRIES.md`, `docs/PLATFORM_BACKUPS_RECOVERY.md`, `docs/PLATFORM_ROLES_PERMISSIONS.md`.
- Routes and middleware: `routes/web.php`, `app/Http/Middleware/EnsureTenantPermission.php`, `EnsureModuleEnabled.php`, `ResolveTenantContext.php`, `EnforceTenantIsolation.php`, `EnforceShopIsolation.php`.
- POS/inventory/shift: `app/Http/Controllers/Tenant/PosController.php`, `CashierShiftController.php`, `InventoryController.php`, `app/Services/SaleService.php`, `InventoryService.php`, `PharmacyService.php`.
- Roles/modules: `app/Services/ProvisioningEngine.php`, `database/seeders/PlatformRoleSeeder.php`, `ModuleCatalogueSeeder.php`, `PharmacyDemoSeeder.php`, `RestaurantTenantSeeder.php`.
- Audit/isolation tests: `app/Services/PlatformAuditVerifier.php`, `PlatformAuditRecorder.php`, `app/Http/Middleware/RecordTenantActivity.php`, `tests/Feature/TenantIsolationTest.php`, `ShopIsolationTest.php`, `PlatformRoleBoundaryTest.php`, `EnsureModuleEnabledTest.php`, `PlatformHardeningAndReleaseReadinessTest.php`, `TenantPosAndShiftTest.php`, `PharmacyCheckoutSafetyTest.php`.

## Remediation — 25 November 2026

This implementation follow-up is **partial**. Local verification passed with 304 tests / 2,802 assertions, Pint, and `npm run build`. MySQL/PostgreSQL CI, live M-Pesa settlement, and production backup/restore drills were not run.

| Finding | Status | Evidence |
|---|---|---|
| P0-1 POS price, discount, tender, idempotency | Partially closed | `SaleService` derives price/tax, caps discounts, enforces idempotency; `TenantPosAndShiftTest` adversarial tests. No multi-process oversell test. |
| P0-2 subscription settlement | Partially closed | Pending attempts and verified reconciliation enforced; `TenantSubscriptionPaymentTest`, `PlatformBillingAndDunningTest`. Subscription awaiting-payment transition and route-level step-up evidence remain incomplete. |
| P0-3 M-Pesa callback | Partially closed | `MpesaCallbackReconciliationTest` covers mismatch, failed result, duplicate and unmatched cases. Callback completion of a POS sale and live-provider verification remain open. |
| P0-4 pharmacy checkout | Partially closed | Checkout batch/prescription validation and entitlement routes; `PharmacyCheckoutSafetyTest`. Controlled-substance policy and pharmacist step-up are incomplete. |
| P0-5 backup/restore | Partially closed | Existing encrypted artifact/checksum/retention and isolated SQLite restore reviewed; real MySQL/PostgreSQL dump and full adversarial restore matrix were not run. |
| P1-1 tenant MFA | Partially closed | Manager/pharmacist roles now require MFA and logout clears the marker; complete sensitive-action step-up and recovery tests remain open. |
| P1-2 tenant context | Partially closed | Missing context now throws and tenant routes require it; queued jobs and allow-all static scan remain open. |
| P1-3 CI/counts | Partially closed | PHP 8.3/8.4 × SQLite/MySQL/PostgreSQL configured with coverage and count guard; remote CI has not run. |
| P1-4 STK linkage | Partially closed | Server-side pending-sale requirement, derived amount, duplicate guard, and throttles added; no pending-sale creation workflow exists yet. |
| P1-5 production assertions | Partially closed | Shared production assertions and `platform:production-check` tested for configuration violations; production deployment environment not boot-tested. |
| P1-6 tenant audit chain | Partially closed | Per-tenant chain wired to sale create/void and stock movement; tamper test passes. Other financial mutation families remain open. |

See `docs/PROGRESS_LOG.md` entries L-0136 through L-0146 for evidence and limitations per item.

---
