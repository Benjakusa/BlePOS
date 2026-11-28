# Tenant Roles, Permissions, and Business Access

## Scope

This document describes the tenant role and access-control behavior implemented in the application. Industry role names provide useful starting points for staff setup; they do not mean every workflow described in product planning is implemented. See [INDUSTRIES.md](INDUSTRIES.md) for the implementation status of each business module.

## Tenant isolation and permission checks

- Tenant roles and permissions are stored per tenant. The role administration screen at `/tenant/roles` only loads roles for the active tenant. Tenant context and tenant-scoped model queries remain required.
- The `tenant.permission` route middleware checks grants on the signed-in user's tenant roles. A matching grant on any assigned role permits the action. Business owners have the tenant-owner administrative exemption.
- Routes also retain module-entitlement checks where applicable. A role grant alone cannot open an industry module that is not enabled for that tenant.
- Analytics routes require `reports.sales.view`. Reports and exports are additionally filtered to the user's assigned shop unless the user is the owner or has `shops.all.manage`. Selecting a shop outside the user's assignments is forbidden; a user without an assigned shop receives no shop-scoped report data.
- Product lookup now checks for an applicable POS, inventory, or catalog permission. Industry operational routes combine module checks with specific permissions.
- Navigation and dashboard cards are hidden according to the same permissions, but hiding a link is only a usability aid; route middleware is the access boundary.

## Role templates and provisioning

When a tenant is provisioned, the industry template's `default_roles` names are created as tenant roles alongside the Business Owner role. The role templates do not create employee accounts. A newly provisioned tenant starts with its owner account; an administrator must invite/create staff and assign each person an appropriate role.

Known role names vary by the selected industry template. Examples include Cashier, Storekeeper, Shop Manager, Dispensing Pharmacist, Stock Controller, Waiter, Bartender, Chef/Kitchen, Restaurant Supervisor, Front Desk Agent, Reservations Officer, Housekeeping Manager, Warehouse Dispatcher, Sales Representative, Credit Controller, Gym Manager, Front Desk Staff, Trainer, Print Shop Manager, Designer, Production Operator, Agrovet Attendant, Credit Officer, Branch Manager, Service Advisor, Lead Mechanic, Parts Clerk, Fleet Coordinator, Inspector, and Driver.

The permission catalog and starter grants cover capabilities enforced by current routes. For example, gym staff, printing staff, agrovet staff, garage staff, fleet staff, and parcel staff receive role-specific permissions; cashier, pharmacist, and selected retail roles receive POS, inventory, or shift permissions. Roles without a matching implemented capability are provisioned with no invented permission. The owner role receives the tenant permission catalog.

To add missing templates to existing tenants, run:

```bash
php artisan tenants:seed-role-templates --all
```

Or target one tenant by its ID:

```bash
php artisan tenants:seed-role-templates 123
```

This command creates only missing role templates and applies defaults to newly created roles. It does not rewrite existing role grants, users, or assignments. Review newly added roles and assign them to staff explicitly.

## Managing custom roles

Users with `users.manage` can create, edit, and delete custom roles in `/tenant/roles`. They can select only permissions defined by the application's supported permission catalog. System roles cannot be edited or deleted, and a role assigned to a user cannot be deleted. Role creation, permission changes, and deletion write before/after context to the tenant activity log.

User creation/edit screens show each assigned role's granted permissions. A person assigned multiple roles receives the union of their grants; there is no deny override.

## Implemented route permission families

| Area | Representative grants | Enforced actions |
| --- | --- | --- |
| POS and receipts | `pos.sale.create`, `pos.sales.view`, `pos.sale.void` | Create sales and payments, view receipts/history, void sales |
| Cashier shifts | `shifts.view`, `shifts.manage` | View shifts; open, reconcile, move cash, and close shifts |
| Catalog and stock | `catalog.products.manage`, `catalog.categories.manage`, `inventory.view`, `inventory.receive`, `inventory.adjust` | Manage catalog, view stock, receive and adjust stock |
| Finance and reports | `accounting.view`, `reports.sales.view` | Accounting screens, analytics, exports, and reports |
| Staff and branches | `users.manage`, `shops.all.manage` | Staff/role administration and cross-branch access |
| Pharmacy | inventory grants and staff administration for prescription setup | Batch/stock and prescription routes; dispensing safety rules are separately enforced in POS |
| Parcel delivery | `parcel.view`, `parcel.receive`, `parcel.dispatch`, `parcel.deliver` | Parcel intake, routing, dispatch, and delivery |
| Gym | `gym.member.manage`, `gym.subscription.manage`, `gym.class.manage`, `gym.checkin.manage`, `gym.trainer.manage` | Member, subscription, class, check-in, and trainer actions |
| Printing | `print.job.create`, `print.job.approve`, `print.quote.manage`, `print.material.manage`, `print.payment.record` | Job, quote, production, materials, and deposit actions |
| Agrovet | `agrovet.product.manage`, `agrovet.customer.manage`, `agrovet.sales.manage`, `agrovet.credit.grant`, `agrovet.credit.approve`, `agrovet.purchase.manage`, `agrovet.report.view` | Product/customer, sale, credit, purchase, and report actions |
| Garage | `garage.job.manage`, `garage.estimate.approve`, `garage.part.manage`, `garage.payment.record`, `garage.mechanic.manage`, `garage.vehicle.manage`, `garage.customer.manage` | Repair jobs, estimates, parts, payments, mechanics, vehicles, and customers |
| Fleet | `fleet.vehicle.manage`, `fleet.booking.manage`, `fleet.inspection.manage`, `fleet.payment.manage`, `fleet.maintenance.manage` | Vehicles, bookings, inspections, payments, and maintenance |

Industry route families also require the corresponding enabled module. Some catalogs include capabilities for planned or partially implemented operations; a permission does not create a missing workflow or UI. Consult `routes/web.php` and `INDUSTRIES.md` before treating a listed module as operationally complete.

## Dashboard behavior

Dashboard workspace selection uses tenant ownership and explicit permissions. Users with reporting access get the manager/reporting workspace; cashiers need both POS creation and shift access to get the cashier workspace. Other staff get a limited staff workspace that lists their roles and grants and does not show owner business-wide metrics. This avoids treating an unrecognized role as an owner.

## Known limits and follow-up work

- Role grants are additive across assigned roles; scoped denies, expiry, and temporary delegation are not implemented.
- Shop assignment controls shop-scoped data, but this change does not add department, service-area, or per-record approval scopes.
- Role names remain suggestions. There is no workflow builder, role template editor, or automatic industry onboarding that creates staff accounts.
- Hotel, wholesale, salon, fuel, and other planned workflows remain incomplete where documented in `INDUSTRIES.md`. Templates may exist before all corresponding functions exist.
- Permission coverage follows the currently implemented route groups and is not a substitute for a comprehensive future security review of every model/action/API entry point.
- Existing tenants retain existing roles and grants when the template command runs; owners should review those grants and assign new roles deliberately.

## Verification

Role provisioning, role management and audit events, dashboard fallback behavior, industry route denial, module entitlement checks, report exports, and branch scoping are covered by the tenant feature tests listed in [TESTING.md](TESTING.md).
