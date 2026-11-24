# BlePOS — Platform Authentication

## 1. Platform Guard

Platform administrator authentication is completely isolated from tenant user authentication:

| Property | Value |
|---|---|
| Guard name | `platform` |
| Provider | `platform_users` (model: `App\Models\PlatformUser`) |
| Session cookie | `PLATFORM_SESSION_COOKIE` (env-configurable) |
| Session table | `platform_sessions` |
| Login route | `platform.login` (`/platform-console/login`) |
| Logout route | `platform.logout` (POST) |

---

## 2. MFA (TOTP)

All platform administrator accounts require TOTP-based MFA:

1. After password authentication succeeds, the user is redirected to the TOTP challenge screen.
2. The user submits a 6-digit TOTP code derived from their `totp_secret` (RFC 6238, SHA-1, 30-second window, ±1 step tolerance).
3. On success, `platform_mfa_verified_at` timestamp is written to session.
4. All platform controller actions require `platform_mfa_verified_at` to be present and within the session lifetime.

### MFA Setup Flow
- During account creation by a super-administrator, a TOTP secret is generated.
- The secret is displayed once as a QR code for scanning into an authenticator app (Google Authenticator, Authy, etc.).
- The secret is stored encrypted in `platform_users.totp_secret`.
- MFA reset requires a two-person approval workflow (Annex A Part 7).

---

## 3. Step-Up Authentication

Sensitive mutations (role grants, MFA resets, tenant archival, encryption key rotation) require step-up authentication in addition to active MFA:

1. User submits their current password and an active TOTP code.
2. On success, `platform_step_up_at` timestamp is written to session.
3. Step-up grants a **5-minute grace window** for the sensitive operation.
4. Mutations attempted without an active step-up receive HTTP 403.

---

## 4. Break-Glass Emergency Access

A break-glass procedure is available for emergency platform access when normal authentication mechanisms are unavailable:

- Requires physical presence and dual-authorisation (two senior administrators).
- All break-glass activations are recorded in `platform_security_events` with `event_type = 'break_glass'`.
- Break-glass sessions have a strict 30-minute timeout.
- A post-incident review is mandatory.

---

## 5. Session Management

| Property | Detail |
|---|---|
| Session table | `platform_sessions` |
| Session lifetime | Configurable via `SESSION_PLATFORM_LIFETIME` (default: 240 minutes) |
| Idle timeout | Session invalidated after 60 minutes of inactivity |
| IP binding | Optional; session bound to `ip_address` recorded at login |
| Revocation | Super administrators can revoke any active platform session via the Security Centre |
| Cookie security | `HttpOnly`, `Secure` (production), `SameSite=Strict` |

---

## 6. References

- `docs/PLATFORM_SECURITY.md` — Full security specification
- `app/Http/Middleware/RequirePlatformMfa.php` — MFA enforcement middleware
- `app/Http/Controllers/Platform/AuthController.php` — Login/logout/TOTP controllers
- `app/Models/PlatformUser.php`, `app/Models/PlatformSession.php`
