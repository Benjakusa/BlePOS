# BlePOS Development Progress

## Project Overview
Multi-tenant POS SaaS built on Laravel 13. Single database, tenant-scoped models. 

---

## Session Log

### Session 1 — Input Standardization
**Goal:** Standardize every input across the entire BlePOS app with a single reusable `<x-input>` component.

**Completed:**
- Built `resources/views/components/input.blade.php` — single component covering text, email, password, number, phone, search, textarea, select/dropdown, date/time, file upload, checkbox, radio, and toggle switch.
- Floating label behavior: label sits inside field at rest, animates up onto the border on focus/value.
- Leading icon, trailing icon, helper text, error state all supported via props.
- Replaced every raw `<input>`, `<select>`, `<textarea>` in every page, form, modal, and drawer with `<x-input>`.
- Removed all conflicting inline styles and old CSS.
- Theme tokens used throughout (Helvetica Neue font, CSS custom properties for colors/radius/shadow).

---

### Session 2 — Tenant Authentication & Onboarding
**Goal:** Create a tenant login route, seed a tenant, auto-generate tenant unique code (6-digit), and document credentials.

**Completed:**
- `TenantAuthController` — login/logout for tenant users at `/tenant/login`.
- `ResolveTenantContext` middleware — resolves tenant from authenticated user's tenant_id.
- `EnsureTenantPermission` middleware — checks granular permissions, owners bypass all checks.
- `EnforceShopIsolation` middleware — scopes all queries to active shop.
- `Tenant::generateUniqueCode()` — generates a unique 6-digit numeric code on tenant creation.
- `RestaurantTenantSeeder` — seeds Savanna Bistro & Grill restaurant tenant with roles, permissions, 4 users, 10 products, 2 registers.
- `HotelTenantSeeder` — seeds Grand Horizon Hotel tenant with roles, permissions, 2 users, 9 products.
- Platform superadmin seeded.

**Credentials:**
| Role | Email | Password | URL |
|------|-------|----------|-----|
| Platform Superadmin | admin@blepos.co.ke | Admin@BlePOS2026! | http://localhost:8000/platform/login |
| Restaurant Owner | restaurant.owner@blepos.co.ke | Owner@Savanna2026! | http://localhost:8000/tenant/login |
| Restaurant Cashier | cashier@savanna.co.ke | Cashier@Savanna2026! | http://localhost:8000/tenant/login |
| Restaurant Inventory | inventory@savanna.co.ke | Inventory@Savanna2026! | http://localhost:8000/tenant/login |
| Restaurant Accountant | accountant@savanna.co.ke | Accounts@Savanna2026! | http://localhost:8000/tenant/login |

---

### Session 3 — Permissions & Staff Management
**Goal:** Fix tenant owner permissions (should access all modules), add staff management UI, enforce role-based access across all modules.

**Completed:**
- `User::isTenantOwner()` — returns true if user owns the tenant; owners bypass ALL permission checks automatically in `EnsureTenantPermission` middleware.
- `TenantUserController` — full CRUD for staff (create/invite, edit, assign roles, assign branches, deactivate).
- `resources/views/tenant/users/` — index, create, edit views using `<x-input>` component.
- 13 granular permissions defined and seeded: `pos.sale.create`, `pos.sales.view`, `pos.sale.void`, `shifts.view`, `shifts.manage`, `catalog.products.manage`, `catalog.categories.manage`, `inventory.view`, `inventory.adjust`, `inventory.receive`, `accounting.view`, `users.manage`, `shops.all.manage`.
- 4 roles seeded for restaurant: **Owner** (all permissions), **Cashier** (pos.sale.create, pos.sales.view, shifts.view, shifts.manage), **Inventory Officer** (inventory.*, catalog.*), **Accountant** (accounting.view, inventory.view, pos.sales.view).
- Sidebar nav guards updated — each module link guarded by appropriate permission.
- Re-seeded restaurant tenant with new structure.

---

### Session 4 — Shift Management Bug Fix
**Goal:** Fix `Undefined variable $shop` on `tenant/shifts/create`.

**Completed:**
- `CashierShiftController@create` — now passes `$shop` (active shop from ShopContext) and `$registers` to the view.
- `resources/views/tenant/shifts/create.blade.php` — updated to use `<x-input type="select">` for shop_id and register_id; added JS to filter registers by shop.
- `TenantPosAndShiftTest` — added `shifts.view` and `shifts.manage` to cashier test helper permissions.
- All 213 tests passing.

---

### Session 5 — Manual Product Search & Restaurant Table Management
**Goal:** Add visual product catalog picker to POS (not barcode-only) and build Restaurant Dining Table management.

#### POS Product Catalog Picker ✅
- `ProductController@lookup` updated to support two modes:
  - `?barcode=` → exact match (single product)
  - `?q=` / `?category_id=` → catalog search (up to 40 products with category_name)
- `resources/views/tenant/pos/checkout.blade.php` — fully rewritten:
  - Visual product card grid (auto-fill responsive)
  - Live search input (fetch on keystroke)
  - Category pill filter buttons
  - Barcode scanner mode (press Enter or "Barcode" button)
  - Dining Table dropdown selector in sidebar (restaurant tenants)
  - `dining_table_id` sent in processSale payload

#### Restaurant Dining Table Management ✅
- **Migration** `2026_11_25_085300_create_restaurant_dining_tables_table` — creates `dining_tables` table; adds `dining_table_id` FK to `sales`.
- **Model** `app/Models/DiningTable.php` — with `BelongsToTenant`, helper methods `markOccupied()`, `markAvailable()`.
- **TableController** `app/Http/Controllers/Tenant/TableController.php` — `index`, `store`, `updateStatus`, `destroy`. Tenant-scoped, JSON-aware.
- **Routes** (registered in `routes/web.php` inside tenant group):
  - `GET  /tenant/tables` → `tables.index` (requires `pos.sale.create`)
  - `POST /tenant/tables` → `tables.store` (requires `users.manage`)
  - `PATCH /tenant/tables/{table}/status` → `tables.status` (requires `pos.sale.create`)
  - `DELETE /tenant/tables/{table}` → `tables.destroy` (requires `users.manage`)
- **Floor Plan View** `resources/views/tenant/tables/index.blade.php`:
  - KPI header cards (available, occupied, reserved, total seats)
  - Grouped by zone, card per table with status badge
  - Seat Guests / Free Table / Reserve / Order via POS actions
  - Add Dining Table modal
- **Sidebar nav link** — "Floor Plan & Tables" added to sidebar under Point of Sale section, guarded by `pos.sale.create`.
- **SaleService** — `createSale()` accepts `$diningTableId` and stores it on the sale.
- **PosController** — `checkout()` passes `$tables` and `$categories` to view; `processSale()` validates `dining_table_id` and calls `$table->markAvailable()` after sale completes.
- **RestaurantTenantSeeder** — seeds 18 dining tables across 4 zones (Main Dining, Terrace & Garden, VIP Lounge, Bar Counter).

#### Test Results
- **213 tests, 213 passed, 2267 assertions** ✅
- **Pint** — zero style violations ✅
- **Vite build** — not run this session (no JS/CSS asset changes)

---

## Current Database State (as of Session 5)

| Entity | Count | Notes |
|--------|-------|-------|
| Tenants | 2 | Restaurant (savanna-bistro-grill), Hotel (grand-horizon-hotel) |
| Restaurant users | 4 | Owner, Cashier, Inventory Officer, Accountant |
| Restaurant products | 10 | Across 6 categories |
| Restaurant dining tables | 18 | 4 zones: Main Dining (8), Terrace (4), VIP (2), Bar (4) |
| Hotel users | 2 | Owner, Receptionist |
| Migrations | All run | dining_tables + sales.dining_table_id added |

---

## Architecture Notes

- **Tenant isolation:** All queries scope by `tenant_id`. `BelongsToTenant` trait adds global scope automatically.
- **Owner bypass:** `User::isTenantOwner()` → `EnsureTenantPermission` middleware skips all checks for owners.
- **Shop context:** `ShopContext` service resolves active shop per session. `EnforceShopIsolation` middleware applies it.
- **Input component:** ALL inputs use `<x-input>` — no raw `<input>`, `<select>`, or `<textarea>` in views.
- **POS lookup:** `GET /tenant/products/lookup?q=NAME` or `?barcode=CODE` — dual mode.
- **SQLite in dev:** No raw SQL dialect differences. Schema changes via migrations only.

---

---

## 2026-11-25 — Multi-Tenant M-Pesa Credential Vault

### Status: DONE

### Done
- [x] Migration: `mpesa_credentials` and `mpesa_credential_audits`
- [x] Encryption service (`App\Services\MpesaEncryptionService`, AES-256-GCM)
- [x] Models + relationships (`MpesaCredential`, `MpesaCredentialAudit` for platform/tenant/branch)
- [x] Tenant & Platform CRUD controllers + `<x-input>` Blade views with masked responses
- [x] Authorization & isolation (tenant scoping + branch-level scoping)
- [x] `MpesaCredentialResolver` (branch → tenant → platform fallback)
- [x] Callback token signing & `MpesaCallbackController` (`/api/mpesa/callback/{token}`)
- [x] `MpesaSandboxSeeder` (platform + 2 demo tenants with tenant & branch sandbox creds)
- [x] Feature tests (`tests/Feature/MpesaCredentialVaultTest.php` — 11 tests, 42 assertions)
- [x] No-secret-in-logs and no-secret-in-responses verification

### Schema
| Table | Purpose |
|-------|---------|
| `mpesa_credentials` | Stores AES-256-GCM encrypted per-owner credentials (`platform`, `tenant`, `branch`) |
| `mpesa_credential_audits` | Change and test audit trail (field names/status only, no secrets) |

### Encryption
- Algorithm: AES-256-GCM (12-byte random IV + 16-byte auth tag + ciphertext, base64-encoded)
- Key source: `MPESA_ENCRYPTION_KEY` (`.env` / `config/services.php`)
- Decrypted in-memory only in `MpesaCredentialResolver` / `MpesaCredentialController::testConnection`
- Secret columns hidden on `MpesaCredential` (`$hidden`) and masked via `••••XXXX` in UI/responses

### API & Web Endpoints Added
- **Tenant (`tenant.mpesa.*`, `tenant.permission:users.manage`):**
  - `GET /tenant/mpesa` (`index`)
  - `GET /tenant/mpesa/create` (`create`)
  - `POST /tenant/mpesa` (`store`)
  - `GET /tenant/mpesa/{mpesaCredential}/edit` (`edit`)
  - `PUT /tenant/mpesa/{mpesaCredential}` (`update`)
  - `DELETE /tenant/mpesa/{mpesaCredential}` (`destroy`)
  - `POST /tenant/mpesa/{mpesaCredential}/test` (`test`, throttled `5,1`)
- **Platform (`platform.mpesa.*`, `platform.permission:platform.tenants.view`):**
  - `GET/POST/PUT/DELETE /platform-console/mpesa/*`
- **Callback:**
  - `POST /api/mpesa/callback/{token}` (`mpesa.callback`, CSRF-exempt, token-validated)

### Sandbox Placeholders
- Platform: shortcode `174379`, env `sandbox`
- Tenant A (`savanna-bistro-grill`): tenant shortcode `174379`, branch shortcode `174380`
- Tenant B (`grand-horizon-hotel`): tenant shortcode `174379`, branch shortcode `174381`
- (All keys/secrets are dummy sandbox placeholders)

### Test Results
- `MpesaCredentialVaultTest`: **11/11 passed (42 assertions)**
  - Encryption round-trip & random IV: PASS
  - Tenant isolation: PASS
  - Branch isolation: PASS
  - Credential resolution order (branch → tenant → platform): PASS
  - Masked API/UI responses & hidden model attributes: PASS
  - Sandbox OAuth mock & audit log: PASS
  - Callback token mapping & rejection of invalid tokens: PASS
  - No-secrets-in-logs verification: PASS
- **Full suite (`php artisan test --compact`):** **230/230 passed (2354 assertions)**
- **Pint (`./vendor/bin/pint --test`):** **PASSED**

### Self-Service Tenant Subscription & POS Enhancements (2026-11-25)
- **Product Images:** Added `image_path` migration, file upload `<x-input type="file">` on product create/edit, and `image_url` in `products.lookup`.
- **POS Layout & STK Push:** Rebuilt `tenant/pos/checkout.blade.php` to fit in viewport with separate Barcode Scan + Manual Search, wrapped Category Filters (no sliders), downward product image grid with strict text containment, and `MpesaStkPushService` (`POST /tenant/pos/mpesa/stk-push`).
- **Tenant Subscription Self-Service Payment:** Added `TenantSubscriptionController` (`GET /tenant/subscription`, `POST /tenant/subscription/pay`), dashboard Subscription & Billing card, sidebar link, and `tenant/subscription/index.blade.php`. Tenants can pay for a plan directly (via Platform M-Pesa STK Push, Paybill ref, Card, or Bank Transfer) even without a prior platform-sent invoice, or settle open invoices.
- **Tests Added:** `PosProductImageAndStkPushTest` (2/2 passed) and `TenantSubscriptionPaymentTest` (4/4 passed).

### Notes / Next Steps
- Add credential rotation reminder notifications
- Wire STK Push initiation directly from POS checkout modal using `MpesaCredentialResolver`

---

## Open / Future Work

- [ ] Write `TenantTableManagementTest` feature tests for table CRUD and status transitions
- [ ] Seed Hotel tenant with appropriate products/rooms (no dining tables needed)
- [ ] Real-time table status updates (WebSockets / polling) for busy restaurant floor
- [ ] Table-to-order linking: when waiter opens POS from a table card, pre-select that table
- [ ] KOT (Kitchen Order Ticket) printing integration
- [ ] Split bill / multi-payment support for tables
- [ ] Vite production build verification after each session
