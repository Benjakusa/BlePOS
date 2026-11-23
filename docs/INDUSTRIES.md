# BlePOS — Industry Modules Specification

## 1. Overview & Strategy

BlePOS rejects a one-size-fits-all model. Businesses in different verticals face distinct regulatory, physical, and operational workflows. Industry modules provide deep, specialized business logic rather than superficial UI banners.

---

## 2. Supported Industry Blueprints

### 2.1 General Retail & Supermarket
- High-throughput barcode scanning with hotkey navigation.
- Product variants (Size, Color, Flavour) and bulk Excel/CSV product imports.
- Promotion engine: Multi-buy (Buy 2 Get 1 Free), bundle pricing, timed discounts.
- Customer loyalty points earning and redemption at checkout.
- Multi-register lane management with supervisor price-override permissions.

### 2.2 Pharmacy & Healthcare
- Mandatory Batch / Lot Number and Expiry Date tracking on all medicine items.
- Automated Near-Expiry Alerts (30, 60, 90 days) and hard sale blocks on expired stock.
- Prescription Management: Doctor details, patient record, dosage instructions, and dispensing log.
- Restricted & Controlled Substances: Requires certified pharmacist credential step-up before dispensing.
- Drug Recall Workflow: Immediate quarantine and tracing of distributed batches by supplier and patient.

### 2.3 Restaurant, Bar & Lounge
- Visual Floor & Table Management: Graphical table layouts, occupied/free status, guest counts.
- Kitchen Display System (KDS) & Order Printing: Instant ticket routing to kitchen and bar printers.
- Recipe & Bill of Materials (BOM): Automatic fractional deduction of raw ingredients upon food sale.
- Bar Tabs & Bill Splitting: Split by seat, item, or custom monetary fraction.

### 2.4 Hotel & Hospitality
- Room inventory categorized by type (Standard, Deluxe, Executive, Suite).
- Reservation Management: Direct bookings, walk-ins, deposits, and room availability calendar.
- Guest Folios: Centralized billing combining accommodation, dining, laundry, and extra amenities.
- Housekeeping Management: Clean, Dirty, Out of Order, Maintenance inspection statuses.
- Hospitality KPIs: Occupancy Rate, Average Daily Rate (ADR), Revenue Per Available Room (RevPAR).

### 2.5 Wholesale & Distribution
- Tiered Pricing: Wholesale, Semi-Wholesale, Retail, and Customer-specific price agreements.
- Minimum Order Quantities (MOQ) and bulk carton/crate packaging units.
- Credit Management: Formal customer credit applications, credit limits, and aging ledger locks.
- Sales Orders, Quotations, Picking Slips, and Proof of Delivery (POD) mobile signatures.

### 2.6 Salon, Spa & Wellness
- Appointment Scheduling: Calendar booking, stylist availability, duration intervals.
- Service Catalogue (Haircuts, Styling, Massage) bundled with retail beauty product checkout.
- Commission Calculations: Automatic computation of stylist service commissions and retail sales bonuses.

### 2.7 Fuel Station & Petroleum Retail
- Bulk Fuel Management: Underground storage tanks (PMS, AGO, DPK) and dispenser pump mapping.
- Shift Readings: Opening/closing electronic meter totals vs mechanical dip measurements.
- Fuel Variance Reconciliation: Evaporation and temperature variance tolerance monitoring.
- Commercial Fleet Card / Account management with restricted fuel product allowances.
