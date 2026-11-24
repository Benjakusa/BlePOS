# BlePOS — KRA eTIMS Fiscalization Architecture

> [!WARNING]
> **REGULATORY PLACEHOLDER ACTIVE — PENDING OFFICIAL KRA CLEARANCE**
> 
> The KRA eTIMS integration module in BlePOS is currently operating in **Technical Simulation / Sandbox Mode** with status **`BLOCKED-ON-CERTIFICATION`**. 
> 
> The platform does **NOT** currently claim live regulatory clearance or transmit real fiscal receipts to KRA production endpoints. All interfaces, APIs, and data structures are configured as faithful placeholders matching KRA VSCU / OSCU specifications for release readiness once certification is formally granted.

---

## 1. Specification Architecture

The architecture prepares BlePOS for Kenya Revenue Authority (KRA) electronic Tax Invoice Management System (eTIMS) requirements:

- **VSCU Integration**: Virtual Sales Control Unit middleware protocol compatibility.
- **Fiscal Signing**: Structure for invoice payload hashing, cryptographic signing, and QR code verification URLs (`https://itax.kra.go.ke/KRA-Portal/...`).
- **Control Codes**: Placeholders for Device Serial Numbers (CU numbers), Internal Data Signatures, and Sequential Fiscal Invoice Counters.
- **Offline Safeguard**: Queuing mechanism designed to buffer untransmitted fiscal payloads locally during network outages.

---

## 2. Certification Tracking Console

The Platform Console monitors readiness criteria in `platform_etims_certifications`:
- Sandbox API Handshake & Payload Validation
- QR Code Format & Verification Link Resolution
- Credit Note & Reversal Fiscal Adjustments
- Cryptographic Signature Generation & Storage
- KRA Production Gateway Sign-Off

Status indicator remains clearly displayed across the Platform Console and Dashboard to ensure honest operational reporting.

---

## 3. Key Models & Code References

- Model: `App\Models\PlatformEtimsCertification`
- Console View: `resources/views/platform/compliance/index.blade.php`
- Controller: `App\Http\Controllers\Platform\ComplianceController`
