# BlePOS — External Integrations Specification

## 1. Safaricom M-Pesa (Daraja API 2.0)

M-Pesa is the primary digital payment rail for retail commerce in Kenya. BlePOS implements a robust, shop-isolated M-Pesa integration.

### 1.1 Architecture & Workflow
- **Lipa Na M-Pesa Online (STK Push)**:
  1. Cashier enters customer mobile number (`2547XXXXXXXX`).
  2. BlePOS backend dispatches an authenticated STK Push request to Safaricom Daraja.
  3. Customer enters their M-Pesa PIN on their mobile handset.
  4. Safaricom fires an asynchronous webhook callback to BlePOS: `POST /api/v1/payments/mpesa/callback/{shop_token}`.
  5. BlePOS matches the `CheckoutRequestID`, validates the transaction amount, marks the sale as paid, opens the cash drawer, and prints the fiscal receipt.
- **C2B (Customer to Business) Manual Till / Paybill Match**:
  - Support for direct Paybill/Till transactions where customer pays before checkout.
  - Background listener matches incoming payments by transaction reference code and marks the corresponding bill.
- **Security & Idempotency**:
  - Webhook endpoints verify HMAC signatures and Safaricom IP ranges.
  - Callbacks use idempotency tokens to prevent duplicate payment creation on retried webhooks.
  - Shop credentials (Consumer Key, Consumer Secret, Passkey, Shortcode) are encrypted at rest using AES-256.

---

## 2. KRA eTIMS (Electronic Tax Invoice Management System)

KRA eTIMS is the mandatory fiscalization system enforced by the Kenya Revenue Authority. Every commercial transaction must generate a cryptographically signed electronic tax invoice.

### 2.1 Integration Modes
1. **OSCU (Online Sales Control Unit)**: Real-time cloud-to-cloud API integration.
2. **VSCU (Virtual Sales Control Unit)**: Server-side cryptographic signing engine with batch transmission.

### 2.2 Operational Flow
- Each sale calculates VAT per item (Standard 16%, Zero Rated 0%, Exempt).
- Sale payload is structured to KRA specifications: Tenant KRA PIN, Branch Code, Buyer PIN (for B2B), Invoice Number, Line Items, HS Codes, Total Tax.
- Fiscal signing server returns: Fiscal Day, Fiscal Receipt Number, Internal Data Hash, and KRA Signature.
- A standardized KRA QR code is generated using `bacon/bacon-qr-code` and printed at the bottom of the thermal receipt.
- Offline Fallback: If KRA endpoints are unreachable, invoices are queued locally with offline fiscal sequences and synchronized automatically upon connection recovery.

---

## 3. POS Peripherals & Hardware Integration

- **ESC/POS Thermal Receipt Printers**: High-speed direct printing to 80mm and 58mm thermal printers via USB, Network (TCP/IP Port 9100), and Bluetooth.
- **Barcode & QR Scanners**: Standard HID keyboard emulation scanners and serial 2D scanners.
- **Electronic Cash Drawers**: Standard 12V/24V pulse kick via the thermal printer RJ11/RJ12 drawer kick port.
- **Customer Facing Displays (CFD)**: Dual-screen support rendering live cart totals and promotional media.
