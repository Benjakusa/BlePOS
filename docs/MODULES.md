# BlePOS — Module Catalogue & Extensibility Specification

## 1. Overview

BlePOS features a versioned, dependency-aware module catalogue. Modules represent discrete business capabilities that can be entitled, activated, or deactivated per tenant according to their subscription plan or purchased add-ons.

---

## 2. Core Modules Catalogue (25 Standard Modules)

| ID | Module Name | Category | Primary Dependencies | Description |
|---|---|---|---|---|
| `mod-pos` | POS & Sales | Core | None | High-speed checkout, barcode scanning, cart, receipts, held sales |
| `mod-products` | Products & Catalog | Core | None | Items, variants, units of measure, categories, SKU/barcode |
| `mod-inventory` | Inventory Control | Core | `mod-products` | Multi-shop stock levels, counts, stock movements, reorder alerts |
| `mod-customers` | Customer Management | Commercial | None | Profiles, credit balances, purchase history, customer groups |
| `mod-suppliers` | Supplier Management | Procurement | None | Vendor records, terms, catalogs, supplier balances |
| `mod-procurement` | Procurement & Purchasing | Procurement | `mod-suppliers`, `mod-products` | Purchase orders, 3-way matching, goods received notes |
| `mod-transfers` | Stock Transfers | Logistics | `mod-inventory` | Inter-shop and warehouse stock requisitions, dispatch, and receipt |
| `mod-expenses` | Expense Tracking | Finance | None | Shop-level expense recording, receipt attachments, approval flows |
| `mod-cash` | Cash & Registers | Core | None | Register shifts, opening float, cash drops, cashier reconciliation |
| `mod-staff` | Staff & RBAC | Core | None | Multi-user roles, granular permissions, shop assignments |
| `mod-accounting` | Double-Entry Accounting | Finance | `mod-expenses`, `mod-pos` | Chart of accounts, general ledger, journals, balance sheet |
| `mod-reports` | Advanced Analytics | Analytics | `mod-pos` | Sales, margin, stock valuation, and tax reporting |
| `mod-multishop` | Multi-Shop Management | Enterprise | None | Centralized branch administration, consolidated reporting |
| `mod-warehousing` | Advanced Warehousing | Logistics | `mod-inventory` | Bin locations, batch picking, warehouse-to-shop distribution |
| `mod-mpesa` | Safaricom M-Pesa | Payments | `mod-pos` | Daraja STK Push, Till/Paybill integration, automated reconciliation |
| `mod-etims` | KRA eTIMS Fiscalization | Compliance | `mod-pos` | Real-time fiscal invoice signing, QR codes, KRA reporting |
| `mod-offline` | Offline-First POS | Core | `mod-pos` | Local client transaction queue, local SQLite cache, sync engine |
| `mod-loyalty` | Customer Loyalty | Marketing | `mod-customers` | Points accumulation, tier discounts, redemption workflows |
| `mod-promotions` | Promotions & Bundles | Commercial | `mod-products`, `mod-pos` | BOGO, happy hour pricing, time-limited promotional campaigns |
| `mod-ecommerce` | Online Ordering Sync | Sales | `mod-products`, `mod-pos` | Web store API, click-and-collect orders, inventory sync |
| `mod-delivery` | Delivery & Dispatch | Logistics | `mod-pos` | Driver assignment, route dispatch, proof of delivery signatures |
| `mod-pharmacy` | Pharmacy Operations | Industry | `mod-inventory`, `mod-products` | Batch/lot numbers, drug expiry control, prescription dispensing |
| `mod-restaurant` | Restaurant & Bar | Industry | `mod-pos`, `mod-inventory` | Table management, kitchen display system (KDS), split bills |
| `mod-hospitality` | Hotel & Lodging | Industry | `mod-pos`, `mod-customers` | Room reservations, folios, guest check-in/out, housekeeping |
| `mod-fuels` | Fuel Station Operations | Industry | `mod-inventory`, `mod-pos` | Tank dips, pump meters, density checks, daily fuel reconciliation |

---

## 3. Module Lifecycle & Rules

1. **Versioned Definitions**: Each module has a semantic version (`1.0.0`), a formal schema requirement, and explicit migration files.
2. **Dependency Resolution**: A module cannot be activated unless all prerequisite modules are actively provisioned and healthy.
3. **Plan Entitlement**: Tenant access is governed by plan boundaries. Attempting to activate an unentitled module requires a plan upgrade or authorized administrative override.
4. **Safe Deactivation**: A module containing transactional data (e.g. accounting, inventory) cannot be deactivated if doing so would corrupt business ledger integrity; it must transition to read-only archival mode.
