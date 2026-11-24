# BlePOS — Platform Compliance & Regulatory Governance

## 1. Regulatory Overview

BlePOS incorporates dedicated tooling to ensure governance across Kenyan and East African regulatory requirements, specifically:
- Kenya Revenue Authority (KRA) eTIMS fiscalization framework.
- Kenya Data Protection Act (DPA) 2019 (DSAR / subject access requests).
- Versioned legal policies (Terms of Service, Privacy Policy, SLA, Data Processing Agreement).

## 2. KRA eTIMS Certification Tracker

The Platform Console monitors technical certification milestones against KRA specifications:

- **Current Status**: `BLOCKED-ON-CERTIFICATION` (Technical Sandbox Simulation Mode).
- **Regulatory Transparency**: Live fiscal transmissions to KRA production endpoints remain blocked pending formal regulatory sign-off.
- **Criteria Tracking**: Tracks status (`pending`, `passed`, `blocked`, `failed`), KRA reference numbers, and verification audit timestamps.

## 3. Data Protection (Kenya DPA 2019)

- Subject Access Requests (DSAR) logging and SLA tracking (statutory 30-day response window).
- Request types: Data export, rectification, erasure/anonymization, objection to processing.
- Tenant data redaction tools with verification logs.

## 4. Legal Document Policy Engine

- Central management of tenant-facing legal agreements (`terms`, `privacy`, `sla`, `dpa`).
- Version history with effective dates and mandatory tenant acceptance logging.

## 5. Key Models & Code References

- Models: `App\Models\PlatformEtimsCertification`, `App\Models\PlatformDataProtectionRequest`, `App\Models\PlatformLegalDocument`
- Controller: `App\Http\Controllers\Platform\ComplianceController`
- View: `resources/views/platform/compliance/index.blade.php`
