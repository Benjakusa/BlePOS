# BlePOS — Platform Roles & Permissions

## 1. Built-In Platform Roles

BlePOS ships with seven built-in platform roles. These roles are seeded during provisioning and cannot be deleted.

| Role Slug | Display Name | Purpose |
|---|---|---|
| `super_admin` | Super Administrator | Full platform access; all permissions; two-person-approval grantee |
| `operations` | Operations | Tenant management, provisioning oversight, module management |
| `billing` | Billing & Finance | Subscription management, invoicing, payment records, dunning |
| `support` | Support Engineer | View tenant data for support cases; create/resolve support tickets |
| `compliance` | Compliance Officer | eTIMS certification tracking, data protection requests, legal documents |
| `security` | Security Officer | Security events, session management, encryption key rotation |
| `readonly` | Read-Only Viewer | Read-only access to dashboards and analytics; no write access |

> [!IMPORTANT]
> The `super_admin` role grant requires a two-person approval workflow. No administrator may grant themselves this role.

---

## 2. Permission Catalogue

Permissions are stored in `platform_permissions` and assigned to roles via `platform_role_permissions`.

### Tenant Management
| Permission | Description |
|---|---|
| `tenants.view` | List and view tenant records |
| `tenants.create` | Provision new tenants |
| `tenants.suspend` | Suspend an active tenant |
| `tenants.reactivate` | Reactivate a suspended tenant |
| `tenants.archive` | Archive a tenant (requires two-person approval) |

### Billing & Subscriptions
| Permission | Description |
|---|---|
| `billing.view` | View plans, subscriptions, invoices |
| `billing.manage` | Create/modify plans and subscriptions |
| `billing.invoices` | Generate and void invoices |

### Modules & Entitlements
| Permission | Description |
|---|---|
| `modules.view` | Browse module catalogue |
| `modules.assign` | Assign modules to tenants |

### Compliance
| Permission | Description |
|---|---|
| `compliance.view` | View eTIMS certification records and data protection requests |
| `compliance.manage` | Update certification status, manage DSAR records |
| `compliance.legal` | Manage published legal policy documents |

### Security & Audit
| Permission | Description |
|---|---|
| `security.view` | View security events and active sessions |
| `security.sessions` | Revoke platform sessions |
| `security.keys` | Rotate encryption keys |
| `audit.view` | View platform audit log |

### Platform Administration
| Permission | Description |
|---|---|
| `platform.users.view` | List platform administrator accounts |
| `platform.users.create` | Create new platform administrator accounts |
| `platform.users.roles` | Assign roles to platform administrators |
| `platform.approvals` | Approve two-person approval requests |

---

## 3. Role-Permission Matrix

| Permission | super_admin | operations | billing | support | compliance | security | readonly |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| tenants.view | ✓ | ✓ | | ✓ | ✓ | ✓ | ✓ |
| tenants.create | ✓ | ✓ | | | | | |
| tenants.suspend | ✓ | ✓ | | | | | |
| tenants.reactivate | ✓ | ✓ | | | | | |
| tenants.archive | ✓ | | | | | | |
| billing.view | ✓ | ✓ | ✓ | | | | ✓ |
| billing.manage | ✓ | | ✓ | | | | |
| compliance.view | ✓ | ✓ | | ✓ | ✓ | | ✓ |
| compliance.manage | ✓ | | | | ✓ | | |
| security.view | ✓ | | | | | ✓ | |
| security.sessions | ✓ | | | | | ✓ | |
| security.keys | ✓ | | | | | ✓ | |
| audit.view | ✓ | ✓ | | | ✓ | ✓ | |
| platform.users.roles | ✓ | | | | | | |
| platform.approvals | ✓ | ✓ | | | | | |

---

## 4. References

- `app/Models/PlatformRole.php`
- `app/Models/PlatformPermission.php`
- `database/seeders/PlatformRoleSeeder.php`
- `database/seeders/PlatformPermissionSeeder.php`
