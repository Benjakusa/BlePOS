# BlePOS — Platform Audit Trail & Cryptographic Integrity

## 1. Overview

BlePOS implements an append-only, tamper-evident audit logging architecture across the entire Platform Control Plane.

## 2. Append-Only SHA-256 Hash Chain

Every row in `platform_audit_logs` is cryptographically linked to the previous log entry:

$$\text{hash}_n = \text{SHA256}(\text{hash}_{n-1} \parallel \text{actor\_id} \parallel \text{event\_type} \parallel \text{target\_type} \parallel \text{target\_id} \parallel \text{metadata} \parallel \text{created\_at})$$

- **Tamper Detection**: If any record is modified, inserted out-of-order, or deleted, all subsequent hashes in the chain become invalid.
- **Database Safeguards**: Update and delete privileges on `platform_audit_logs` are prohibited.

## 3. Audit Verification Command

Platform administrators can verify the integrity of the audit chain at any time using the Artisan command:

```bash
php artisan platform:audit-verify
```

Expected output when intact:
```
Audit chain intact. Checked 152 entries at 2026-11-24 14:00:00.
```

If tampering or hash mismatch is detected, the command terminates with exit code 1 and outputs the exact record ID where the chain was broken.

## 4. Audit Chain Lock Recovery

The recorder uses the singleton row `id = 1` in `platform_audit_chain_locks` as its transaction mutex. Login and other audited operations fail closed if the row is missing. The migration `2026_11_28_000007_restore_platform_audit_chain_lock` restores the row idempotently without changing audit entries. Run the normal database migration command after deploying:

```bash
php artisan migrate --force
```

Do not delete this row when clearing application data; it is required infrastructure for serialized audit writes.

## 5. Key Models & Code References

- Model: `App\Models\PlatformAuditLog`
- Command: `App\Console\Commands\AuditVerifyCommand`
- Test: `tests/Feature/PlatformHardeningAndReleaseReadinessTest::test_cryptographic_audit_trail_integrity_command`
