# BlePOS — Platform Industry Templates

## 1. Overview

BlePOS uses versioned industry templates to automatically tailor terminology, navigation, default product categories, and enabled modules to specific business verticals during tenant provisioning.

## 2. Supported Industry Templates

| Template Code | Vertical | Key Customizations & Modules |
|---|---|---|
| `retail` | General Retail & Supermarkets | Barcodes, variants, multi-buy promotions, customer loyalty |
| `pharmacy` | Pharmacy & Healthcare | Batch numbers, expiry alerts, doctor prescriptions, controlled drugs |
| `restaurant` | Restaurant, Bar & Lounge | Floor maps, table states, kitchen display tickets (KDS), split bills |
| `services` | Salons, Spas & Professional | Service bookings, appointments, staff commission tracking |
| `hardware` | Hardware & Construction | Fractional units (kg, meters), contractor accounts, bulk pricing |
| `electronics`| Electronics & Appliances | Serial number tracking, warranty cards, repair service tickets |
| `wholesale` | Wholesale & Distribution | Tiered volume pricing, trade credit, multi-warehouse dispatches |

## 3. Template Versioning & Immutability

- Industry templates are versioned in `industry_templates`.
- When a tenant is provisioned, their configuration is instantiated from the active template version.
- Updating an industry template does NOT retroactively modify running tenant settings or schema.

## 4. Key Models & Code References

- Model: `App\Models\IndustryTemplate`
- Seeder: `database/seeders/IndustryTemplateSeeder.php`
- Service: `App\Services\ProvisioningEngine`
