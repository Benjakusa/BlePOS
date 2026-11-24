# BlePOS — Platform Modules & Entitlements

## 1. Modular Architecture

BlePOS provides a modular feature catalogue where business capabilities can be licensed, enabled, or disabled per tenant based on their plan or purchased add-ons.

## 2. Standard 25-Module Catalogue

| Code | Module Name | Category | Primary Dependencies |
|---|---|---|---|
| `mod-pos` | POS & Checkout | Core | None |
| `mod-products` | Products & Catalog | Core | None |
| `mod-inventory` | Stock Management | Core | `mod-products` |
| `mod-customers` | Customer Directory | Commercial | None |
| `mod-suppliers` | Supplier Directory | Procurement | None |
| `mod-procurement` | Purchase Orders & GRN | Procurement | `mod-suppliers`, `mod-products` |
| `mod-transfers` | Stock Transfers | Logistics | `mod-inventory` |
| `mod-expenses` | Expense Tracking | Finance | None |
| `mod-cash` | Cashier Shifts & Drawer | Core | None |
| `mod-staff` | Staff & Multi-User RBAC | Core | None |
| `mod-accounting` | Double-Entry General Ledger | Finance | `mod-expenses`, `mod-pos` |
| `mod-reports` | Analytics & Reports | Analytics | `mod-pos` |
| `mod-multishop` | Multi-Shop & Consolidated View | Enterprise | None |
| `mod-warehousing`| Advanced Warehousing | Logistics | `mod-inventory` |
| `mod-mpesa` | Safaricom M-Pesa Integration | Payments | `mod-pos` |
| `mod-etims` | KRA eTIMS Fiscal Compliance | Compliance | `mod-pos` |
| `mod-offline` | Offline-First POS Engine | Core | `mod-pos` |
| `mod-loyalty` | Loyalty Points & Rewards | Marketing | `mod-customers` |
| `mod-promotions` | Promotion & Discount Rules | Marketing | `mod-products` |
| `mod-tables` | Restaurant Table Management | F&B | `mod-pos` |
| `mod-kitchen` | Kitchen Display System (KDS) | F&B | `mod-tables` |
| `mod-prescriptions`| Pharmacy Prescriptions & Rx | Healthcare | `mod-pos` |
| `mod-batches` | Batch & Expiry Tracking | Healthcare | `mod-inventory` |
| `mod-hardware` | Thermal Printer & Scanner SDK | Hardware | `mod-pos` |
| `mod-api` | Tenant REST API Access | Developer | None |

## 3. Dependency Management & Safe Deactivation

- Enabling a module automatically validates that all prerequisite modules are enabled.
- Deactivating a module preserves all historical tenant data in the database.
- Missing dependencies block activation with an explicit error list.

## 4. Key Models & Code References

- Models: `App\Models\Module`, `App\Models\TenantModule`
- Middleware: `App\Http\Middleware\EnsureModuleEnabled`
