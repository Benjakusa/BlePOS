# BlePOS — Industry Modules Specification

## 1. Overview & Strategy

This file is a product roadmap, not a list of completed workflows. The operational baseline today is generic POS/inventory, simple restaurant tables, and pharmacy batch/policy backend connected to checkout. Every other listed capability remains planned unless a current route, screen, authorization rule, and end-to-end test demonstrate it.

---

## 2. Implemented baseline and planned workflows

### 2.1 General Retail & Supermarket — partial baseline
Generic product catalog, inventory, register, shift, and server-priced POS are implemented. Variants, bulk import, promotions, loyalty, and advanced scanner/browser acceptance remain planned.

### 2.2 Pharmacy & Healthcare — backend safety baseline
POS checks batch availability, expiry, quarantine, and controlled-substance authorization for pharmacy tenants and records dispensing. Prescription metadata can require an active prescription record. A complete prescription creation/review UI, prescription-product/quantity linking, verified pharmacist licensing, recall workflow, and clinical audit screens remain planned. Do not use this module for regulated dispensing until those controls and production validation are complete.

### 2.3 Restaurant, Bar & Lounge — partial baseline
Basic table records/status and checkout linkage are implemented. Graphical floor layout, KDS/printing, recipes/BOM, tabs, and bill splitting remain planned.

### 2.4 Hotel & Hospitality — planned
- Room inventory categorized by type (Standard, Deluxe, Executive, Suite).
- Reservation Management: Direct bookings, walk-ins, deposits, and room availability calendar.
- Guest Folios: Centralized billing combining accommodation, dining, laundry, and extra amenities.
- Housekeeping Management: Clean, Dirty, Out of Order, Maintenance inspection statuses.
- Hospitality KPIs: Occupancy Rate, Average Daily Rate (ADR), Revenue Per Available Room (RevPAR).

### 2.5 Wholesale & Distribution — planned
- Tiered Pricing: Wholesale, Semi-Wholesale, Retail, and Customer-specific price agreements.
- Minimum Order Quantities (MOQ) and bulk carton/crate packaging units.
- Credit Management: Formal customer credit applications, credit limits, and aging ledger locks.
- Sales Orders, Quotations, Picking Slips, and Proof of Delivery (POD) mobile signatures.

### 2.6 Salon, Spa & Wellness — planned
- Appointment Scheduling: Calendar booking, stylist availability, duration intervals.
- Service Catalogue (Haircuts, Styling, Massage) bundled with retail beauty product checkout.
- Commission Calculations: Automatic computation of stylist service commissions and retail sales bonuses.

### 2.7 Fuel Station & Petroleum Retail — planned
- Bulk Fuel Management: Underground storage tanks (PMS, AGO, DPK) and dispenser pump mapping.
- Shift Readings: Opening/closing electronic meter totals vs mechanical dip measurements.
- Fuel Variance Reconciliation: Evaporation and temperature variance tolerance monitoring.
- Commercial Fleet Card / Account management with restricted fuel product allowances.
