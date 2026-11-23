# BlePOS — Dashboards & Reporting Specification

## 1. Core Principles & Data Integrity

All dashboards in BlePOS adhere to the following mandatory standards:
1. **Zero Fake Data**: Every metric, chart, and counter is derived from real database transactions, scheduler aggregations, or live health probes. If a service is offline, it displays `Unavailable` or `Not configured` with the last known timestamp.
2. **Role-Based Scoping**: Users only see dashboards and widgets aligned with their verified RBAC permissions. Unauthorized widgets are suppressed entirely at the API and template layer.
3. **Strict Shop Scoping**: Dashboards respect branch isolation. A Shop Manager or Cashier sees data for their assigned shop only. Multi-shop aggregation is restricted to owners and authorized headquarters personnel.
4. **Performance & Caching**: Heavy metric aggregations (e.g. monthly sales trends, inventory valuations) are computed using optimized SQL grouping and cached for 60–120 seconds with automatic invalidation on relevant state changes.

---

## 2. Platform Control Plane Dashboard

### 2.1 Tenant KPIs
- Total Registered Tenants, Active Tenants, In Provisioning, Suspended, Churned.
- Active Shops, Active Terminals, Active Platform Sessions.
- Conversion Funnel: Registered → Provisioned → First Login → First Sale → Paid Subscription.

### 2.2 Financial & Revenue Metrics
- Monthly Recurring Revenue (MRR), Annual Recurring Revenue (ARR), Net New MRR.
- Paid Invoices vs Overdue Invoices, M-Pesa Platform Collection Success Rate.
- Revenue by Plan (Starter, Growth, Enterprise) and Revenue by Industry.

### 2.3 Operational Health & Infrastructure
- Application Worker Status, Scheduler Health, Queue Depth, Failed Jobs Count.
- Database Connection Pools & Storage Utilization.
- KRA eTIMS Transmission Queue & Error Rate.
- M-Pesa Gateway Webhook Latency & Reconciliation Discrepancies.

---

## 3. Tenant Role-Based Dashboards

### 3.1 Business Owner / Managing Director
- Gross Sales, Net Sales, Tax Collected, Cost of Goods Sold (COGS), Gross Margin.
- Payment Breakdown (Cash, M-Pesa, Card, Credit).
- Inter-Branch Comparison: Top Performing Shops vs Underperforming Branches.
- Fast Movers, Slow Moving Stock, Out-of-Stock Warnings.
- Consolidated Cash Position & Accounts Receivable Aging.

### 3.2 Shop Manager
- Daily & Hourly Branch Sales Velocity vs Target.
- Active Cashier Shifts & Register Variances (Over/Short).
- Held Sales Count, Pending Returns/Refunds Requiring Approval.
- Branch Stock-outs, Near-Expiry Items, Incoming Stock Transfers.

### 3.3 Cashier POS Dashboard
- Current Register Identifier & Shift Opening Time.
- Opening Float, Cash in Drawer, Shift Net Sales.
- Transaction Counter & Held Sales Drawer.
- Fiscal Receipt Status (eTIMS transmission acknowledgement).
- *Explicitly Restricted*: Cost price, gross margin, owner profit, and other branch data are completely hidden.

### 3.4 Accountant
- Real-time Trial Balance and General Ledger Activity.
- Accounts Receivable (Debtors) & Accounts Payable (Creditors) Ledgers.
- Bank & M-Pesa Reconciliation Status (Unmatched receipts).
- VAT / Tax Liability Summary & Period Closing Status.

### 3.5 Inventory Manager
- Total Inventory Valuation (FIFO / Weighted Average).
- Low Stock & Reorder Trigger List per Shop and Warehouse.
- Expiring Batch Tracking & Quarantine Stock.
- Stock Count Discrepancies & Stock Movement Audit Trail.
