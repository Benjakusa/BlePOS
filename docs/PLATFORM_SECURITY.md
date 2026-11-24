# BlePOS — Platform Security

## 1. Security Architecture Overview

BlePOS implements defense-in-depth security across the platform control plane:

| Layer | Control |
|---|---|
| **Authentication** | Separate `platform` guard with dedicated session cookie, TOTP MFA mandatory |
| **Authorisation** | 7 built-in platform roles, fine-grained permission checks on every action |
| **Step-Up Auth** | 5-minute grace window required for sensitive mutations |
| **Two-Person Approval** | Required for Super Admin grants, MFA resets, tenant archival |
| **Audit Trail** | Append-only SHA-256 hash chain in `platform_audit_logs`; tamper detection via `platform:audit-verify` |
| **Session Security** | IP-bound sessions, idle timeout, platform-separate session table, admin revocation |
| **CSP** | Strict Content-Security-Policy with per-request nonces on the platform console |
| **Encryption Key Management** | Key rotation with step-up auth, versioned key records in `platform_encryption_keys` |

---

## 2. Authentication

See `docs/PLATFORM_AUTHENTICATION.md` for the full authentication specification.

---

## 3. Authorisation & Permissions

See `docs/PLATFORM_ROLES_PERMISSIONS.md` for the roles and permission catalogue.

---

## 4. Audit Trail

All significant platform actions are recorded in `platform_audit_logs`:

- **Append-only**: No `UPDATE` or `DELETE` operations are permitted on audit log rows.
- **SHA-256 Hash Chain**: Each row stores a `hash` computed over its own fields plus the previous row's hash. This creates a cryptographic chain that detects any tampering, deletion, or reordering.
- **Verification**: Run `php artisan platform:audit-verify` to verify chain integrity. Output: `Audit chain intact. Checked N entries.`
- **Fields**: `actor_id`, `actor_type`, `event_type`, `description`, `ip_address`, `user_agent`, `metadata` (JSON), `hash`, timestamps.

---

## 5. Two-Person Approval

Irreversible operations that require two separate platform administrators to authorise:

| Operation | Trigger |
|---|---|
| Super Administrator role grant | Prevents any single administrator from self-elevating |
| MFA reset for privileged accounts | Protects against account takeover via social engineering |
| Tenant archival / data deletion | Prevents accidental or malicious data destruction |

The requesting administrator creates a `PlatformApproval` record. A second administrator (not the requester) approves it. Self-approval is blocked at the application and database level and verified by automated tests.

---

## 6. Session Security

- Platform sessions are stored in the `platform_sessions` table (not the shared `sessions` table).
- Sessions record `ip_address`, `user_agent`, `logged_in_at`, `expires_at`, `last_active_at`.
- Super administrators can enumerate and revoke any active platform session via the Security Centre.
- Failed TOTP attempts are logged to `platform_security_events` with `event_type = 'mfa_failed'`.

---

## 7. IP Allowlisting

The platform console supports IP address restriction:
- Allowed IP ranges are configured per environment.
- Requests from outside the allowed range receive HTTP 403 before authentication.

---

## 8. Encryption Key Management

- Encryption keys are versioned in `platform_encryption_keys`.
- Key rotation is a step-up-authenticated operation recorded in the audit trail.
- All sensitive fields (TOTP secrets, API credentials) are encrypted at rest using the active key.

---

## 9. Security Events

Security-significant events are recorded in `platform_security_events`:

| Event Type | Trigger |
|---|---|
| `login_success` | Successful platform login |
| `login_failed` | Failed password attempt |
| `mfa_failed` | Invalid TOTP code submitted |
| `mfa_verified` | Successful TOTP verification |
| `step_up_granted` | Step-up authentication completed |
| `session_revoked` | Session forcibly terminated by administrator |
| `break_glass` | Emergency break-glass access activated |
| `role_granted` | Platform role assigned to user |
| `tenant_archived` | Tenant permanently archived |

---

## 10. References

- `docs/PLATFORM_AUTHENTICATION.md` — Authentication detail
- `docs/PLATFORM_ROLES_PERMISSIONS.md` — Roles and permissions
- `docs/PLATFORM_AUDIT.md` — Audit trail specification
- `app/Console/Commands/AuditVerifyCommand.php` — Audit chain verification command
- `app/Http/Middleware/RequirePlatformMfa.php`
- `app/Models/PlatformAuditLog.php`, `app/Models/PlatformSecurityEvent.php`
