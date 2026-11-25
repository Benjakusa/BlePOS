# BlePOS — Platform API Specification

## 1. Overview

BlePOS does **not currently expose a public REST API**. This document is a target specification only; no `/api/v1` routes, Sanctum API authentication, webhook signing, or public API guarantees exist. The M-Pesa callback route is a separate provider integration endpoint. Build a versioned API only when an integration is approved and define its authentication and tenant boundaries before publishing clients.

## 2. Security & Boundaries

- **Authentication**: API requests are authenticated via secure bearer tokens (`Sanctum`) or session cookies depending on the calling context.
- **Tenant Scoping**: All API routes are strictly scoped to the calling organization. Supplying another tenant's ID in request headers or body payloads results in immediate rejection (HTTP 403/404).
- **Rate Limiting**: Tiered rate limits protect endpoints from brute force and denial of service.
- **Idempotency**: Mutation endpoints support `Idempotency-Key` headers to prevent duplicate operations during network retry loops.

## 3. Core Resource Endpoints

- `GET /api/v1/shops` — List shops accessible to caller.
- `GET /api/v1/products` — Filter products, categories, variants, and stock balances.
- `POST /api/v1/sales` — Checkout transaction submission with line items and payments.
- `GET /api/v1/sales/{id}` — Fetch fiscal receipt and payment details.
- `POST /api/v1/stock-movements` — Inter-branch stock transfers and adjustment logs.

## 4. Error Responses

Standard JSON error format:
```json
{
  "error": "resource_not_found",
  "message": "The requested item does not exist in the active tenant scope.",
  "status": 404
}
```
