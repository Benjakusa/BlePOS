# BlePOS — Platform Incident Response & SecOps

## 1. Overview

This document specifies the standard operating procedure for handling security incidents, data breaches, and platform anomalies.

## 2. Incident Classification

| Severity | Criteria | Response Target |
|---|---|---|
| **SEV-1 (Critical)** | Compromise of platform administrator credentials, active data leak across tenants, cryptographic audit chain break. | < 15 minutes |
| **SEV-2 (Major)** | Unplanned outage of primary database, widespread POS checkout failure, queue lockups. | < 30 minutes |
| **SEV-3 (Moderate)**| Individual shop isolation error, transient payment webhook failures. | < 2 hours |
| **SEV-4 (Minor)** | Minor UI reporting discrepancy, non-impacting background job error. | Next business day |

## 3. Incident Workflow

```
[Detection / Alert] ──► [Containment] ──► [Eradication] ──► [Recovery & Post-Mortem]
```

1. **Detection & Triage**:
   - Automated alerts from `platform_security_events` (repeated MFA failures, unauthorized role modifications).
   - Audit integrity check failure (`platform:audit-verify`).
2. **Containment**:
   - Terminate active compromised sessions via Platform Security Centre.
   - Restrict offending IP ranges via web server or firewall rules.
   - In catastrophic scenarios, invoke break-glass protocols to isolate affected nodes.
3. **Investigation & Forensic Analysis**:
   - Inspect append-only `platform_audit_logs`.
   - Verify timeline of events using non-tamperable SHA-256 chain anchors.
4. **Post-Incident Review**:
   - Mandatory post-mortem artifact published within 48 hours.
