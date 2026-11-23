# BlePOS — Security Architecture Specification

## 1. Threat Model & Security Posture

BlePOS handles high-value commercial transactions, taxation records (KRA eTIMS), payment gateway credentials (M-Pesa Daraja), and multi-tenant operational data. The system is designed under a Zero Trust internal architecture.

---

## 2. Authentication & Session Security

### 2.1 Guard & Cookie Isolation
- **Platform Console**:
  - Guard: `platform` (`auth:platform`)
  - User Provider: `platform_users` table
  - Cookie: `PLATFORM_SESSION_COOKIE` (Strict lifetime: 30-minute idle timeout, 8-hour absolute maximum)
  - Flags: `HttpOnly`, `Secure`, `SameSite=Lax`
- **Tenant Application**:
  - Guard: `web`
  - User Provider: `users` table
  - Cookie: `SESSION_COOKIE`
  - Cross-guard session reuse is rejected by session validation middleware (`TrackPlatformSession`).

### 2.2 Multi-Factor Authentication (MFA)
- Required for all platform users and privileged tenant administrators.
- Algorithm: RFC 6238 TOTP (Time-based One-Time Password) with 30-second time steps.
- Secrets: Encrypted at rest using AES-256-GCM via Laravel encryption.
- Backup: 8 cryptographically secure single-use recovery codes, hashed with `bcrypt`/`argon2id` prior to storage.
- Rate Limiting: 5 attempts per 5 minutes per user before temporary account lockout.

### 2.3 Step-Up Authentication
- For any sensitive configuration mutation (user status change, role update, approval action, session revocation):
  - User must confirm password + active TOTP code.
  - Grants a strict 5-minute step-up grace period stored in session (`platform_step_up_at`).
  - Mutations attempted without active step-up are denied with HTTP 403.

### 2.4 Two-Person Approvals
- Applied to irreversible operations:
  - Super Administrator role grants.
  - MFA reset requests for privileged accounts.
  - Tenant archival and permanent database deletion.
- Guardrails:
  - Requester cannot approve their own request (`abort_if(requested_by === user_id, 403)`).
  - Super Administrator role grants require approval by an existing Super Administrator.
  - Approvals carry strict expiry windows (default 24 hours).

---

## 3. Cryptographic Audit Chain

All security events and administrative actions write to an append-only cryptographic ledger (`platform_audit_log`):
- Schema: `id`, `actor_id`, `action`, `target_type`, `target_id`, `details`, `previous_hash`, `entry_hash`, `occurred_at`.
- Hash Formula:
  $$\text{entry\_hash} = \text{SHA256}(\text{previous\_hash} \parallel \text{actor\_id} \parallel \text{action} \parallel \text{target\_type} \parallel \text{target\_id} \parallel \text{details\_json} \parallel \text{occurred\_at})$$
- Database Triggers: SQLite triggers prevent `UPDATE` and `DELETE` queries on the audit table.
- Verification CLI: `php artisan platform:audit-verify` walks the entire chain from genesis to head, asserting uninterrupted cryptographic integrity.

---

## 4. Transport & Network Security

- **Content Security Policy (CSP)**: Nonce-based script execution (`script-src 'self' 'nonce-{nonce}'`), `frame-ancestors 'none'`, `object-src 'none'`.
- **HSTS**: `Strict-Transport-Security: max-age=31536000; includeSubDomains` in production.
- **HTTPS Enforcement**: Automatic HTTP to HTTPS 308 redirect; respects trusted proxies (`TRUSTED_PROXIES` in `config/trustedproxy.php`).
- **IP Allowlisting**: Optional CIDR and IP restriction on the platform console (`PLATFORM_IP_ALLOWLIST_ENABLED`). Includes an audited emergency override CLI command:
  ```bash
  php artisan platform:ip-allowlist-emergency-override --minutes=15 --reason="Emergency incident response"
  ```

---

## 5. Defense Against Common Vulnerabilities

1. **SQL Injection**: Complete reliance on Eloquent ORM and PDO parameterized queries. Raw queries require senior peer review and strict parameter binding.
2. **Cross-Site Scripting (XSS)**: Blade automatic HTML escaping `{{ $var }}` by default. Unescaped `{!! $var !!}` is restricted to vetted SVG icons and sanitised rich text.
3. **Cross-Site Request Forgery (CSRF)**: Verified on all state-altering HTTP methods (`POST`, `PUT`, `PATCH`, `DELETE`).
4. **Formula Injection (CSV Injection)**: All CSV/Excel exports sanitize fields beginning with `=`, `+`, `-`, `@`, `\t`, `\r` by prefixing a single apostrophe `'`.
5. **Secrets Exposure**: Secrets (API keys, M-Pesa Consumer Secrets, eTIMS private keys) are write-only in UI forms and masked in API responses.
