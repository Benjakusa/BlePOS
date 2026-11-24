# BlePOS — Safaricom M-Pesa Daraja Integration

> [!WARNING]
> **INTEGRATION PLACEHOLDER ACTIVE — SANDBOX SIMULATION MODE**
> 
> Safaricom M-Pesa (Daraja API) integration in BlePOS is currently operating in **Sandbox Simulation Mode**.
> 
> The platform includes the end-to-end data models, transaction ledger fields, STK Push workflows, and callback handling structures as tested placeholders. Live production API credentials and production Daraja endpoints will be connected upon completion of telecommunications onboarding. The platform never claims active live production clearing during this staging period.

---

## 1. Planned Workflow Architecture

BlePOS is designed around Safaricom's Daraja 2.0 API suite:

1. **Lipa na M-Pesa Online (STK Push / C2B)**:
   - Cashier enters customer mobile number on checkout.
   - BlePOS dispatches an STK prompt to customer handset.
   - Transaction status updates asynchronously via secure webhook callback.
2. **Till / Paybill Reconciliation**:
   - Automated reconciliation between external M-Pesa receipt codes (`ReceiptNo`, e.g., `SLK28DJ82Z`) and internal `SalePayment` records.
   - Double-entry ledger reflects M-Pesa clearing accounts accurately.
3. **Shop-Level Scoping**:
   - Different branch shops can bind distinct Shortcodes, Passkeys, and Till Numbers according to merchant organization setup.

---

## 2. Security & Idempotency Safeguards

- **Webhook Validation**: Callbacks verify origin headers and cryptographic signatures.
- **Idempotent Processing**: `CheckoutRequestID` and `MerchantRequestID` uniqueness enforcement prevents duplicate payment crediting.
- **Callback Logging**: Raw callback payloads are recorded for forensic reconciliation.

---

## 3. Key Models & References

- Migration: `database/migrations/2026_11_22_000007_create_sales_tables.php` (`sale_payments`)
- Trait: `App\Models\Concerns\BelongsToShop`
