# BlePOS — Platform Support Centre

## 1. Overview

The Platform Support Centre enables platform operators to triage customer inquiries, inspect operational issues, and publish system announcements without violating tenant data isolation.

> [!IMPORTANT]
> Support personnel have **zero direct access to tenant customer data** or transactional business records. All support actions are non-invasive and do not utilize impersonation.

## 2. Core Capabilities

- **Support Case Tracking**: Priority levels (`low`, `medium`, `high`, `urgent`), status lifecycle (`open`, `investigating`, `waiting_tenant`, `resolved`, `closed`).
- **Internal Notes & Triage**: Staff-only collaboration notes on support tickets.
- **Platform Announcements**: Broadcast maintenance notices, feature releases, and service advisories directly to tenant dashboards.
- **Tenant Health Quick-Lookup**: Inspect provisioning status, subscription tier, and error logs without exposing underlying business documents.

## 3. Key Models & Code References

- Models: `App\Models\PlatformSupportTicket`, `App\Models\PlatformAnnouncement`
- Controller: `App\Http\Controllers\Platform\SupportController`
- Routes: `routes/web.php` (`platform.support.*`)
