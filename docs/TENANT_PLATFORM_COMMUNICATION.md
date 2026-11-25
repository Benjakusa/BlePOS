# Tenant and Platform Communication

## Tenant support tickets

Signed-in tenant users can open **Support Tickets** from the tenant navigation. A ticket is assigned to the current tenant by server-side tenant context; the submitted form cannot choose a tenant. It records the signed-in user as requester and receives the platform support SLA based on priority.

Tenants can view and reply to tickets at `/tenant/support/cases`. Case queries always include the active tenant ID. The tenant conversation hides platform notes marked internal. Resolved and closed tickets cannot receive tenant replies.

Platform support agents continue to manage cases under `/platform-console/support/cases`. Public platform replies and status changes notify tenant users. Internal notes remain private and do not notify the tenant. New tenant tickets and replies notify active platform users who have the support-view permission.

## Announcements

Platform announcements published from the Support Centre are now available under **Announcements** in the tenant navigation. The tenant feed observes publication start/expiry and audience targeting: all tenants, one tenant, an industry, or a plan ID/slug. Publication creates an in-app notification for users in the matching tenant audience.

## Platform invoices and due countdown

When a platform administrator issues an invoice, every user account in the billed tenant receives an in-app billing notification linking to **Subscription & Billing**. The invoice is listed there with its due date and day-based countdown: “Due in N days”, “Due today”, or “Overdue by N days”. Paid and voided invoices display their final state instead. Tenant invoice queries and payment checks remain scoped to the active tenant.

The countdown uses the invoice's date-only due date and updates when the billing page is loaded. Platform database notifications are stored in Laravel's `notifications` table and the bell opens a notification inbox with unread counts and mark-as-read actions.

## Routes

| Action | Route name |
| --- | --- |
| Tenant ticket list/create/reply | `tenant.support.cases.*` |
| Tenant announcements | `tenant.announcements.*` |
| Tenant notification inbox/read | `tenant.notifications.*` |
| Platform notification inbox/read | `platform.notifications.*` |
| Platform support | `platform.support.cases.*` |
| Platform invoice creation | `platform.invoices.store` |

## Verification

`tests/Feature/TenantPlatformCommunicationTest.php` covers ticket creation, public and internal message visibility, cross-tenant ticket isolation, notification delivery and read actions, platform invoice delivery/countdown, and tenant-targeted announcement visibility.
