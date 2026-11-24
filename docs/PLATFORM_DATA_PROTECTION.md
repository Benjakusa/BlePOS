# BlePOS — Platform Data Protection & Kenya DPA 2019

## 1. Overview & Regulatory Alignment

BlePOS is architected to comply with the **Kenya Data Protection Act 2019 (DPA)** and regional privacy frameworks.

## 2. Subject Access Request (DSAR) Management

The Platform Console includes a dedicated DSAR tracker (`/platform-console/compliance/data-protection`):

- **Statutory Window**: Enforces the statutory 30-day response timeline with warning indicators for overdue requests.
- **Request Workflows**:
  - `access`: Export of personal data in structured JSON format.
  - `rectification`: Correction of inaccurate personal records.
  - `erasure`: Anonymization / purging of subject records where retention requirements do not supersede.
  - `objection`: Logging processing objections and restriction flags.

## 3. Privacy Policy & Tenant Consent

- Versioned privacy policy documents are managed through the Legal Console.
- Platform tracks tenant administrator consent timestamps on initial provisioning and upon policy revision publication.

## 4. Key Models & Code References

- Model: `App\Models\PlatformDataProtectionRequest`
- Migration: `database/migrations/2026_11_23_130000_create_compliance_and_etims_tables.php`
- Controller: `App\Http\Controllers\Platform\ComplianceController`
