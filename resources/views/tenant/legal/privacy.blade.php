@extends('tenant.layout')

@section('title', 'Privacy Policy · Kenya Data Protection Act, 2019')

@section('content')
<div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
            <a href="{{ route('tenant.legal.index') }}" class="muted" style="text-decoration: none; font-size: 13px;">
                <i class="bi bi-arrow-left"></i> Legal Documents
            </a>
            <span class="muted">&bull;</span>
            <span class="badge badge-success">Kenya DPA 2019 Conforming</span>
            <span class="badge badge-info">Version 2.1 (Active)</span>
        </div>
        <h1 class="page-title">Privacy Policy &amp; Data Protection Statement</h1>
        <p class="muted" style="margin-top: 4px; font-size: 14px;">
            Enacted pursuant to the <strong>Data Protection Act, 2019 (Act No. 24 of 2019)</strong> of the Republic of Kenya.
        </p>
    </div>
    <div style="display: flex; gap: 10px;">
        <button onclick="window.print()" class="btn btn-secondary">
            <i class="bi bi-printer"></i>
            <span>Print Policy</span>
        </button>
        <a href="{{ route('tenant.legal.terms') }}" class="btn btn-outline-primary">
            <span>Terms of Service</span>
            <i class="bi bi-arrow-right"></i>
        </a>
    </div>
</div>

{{-- Metadata Header Strip --}}
<div class="card p-3 mb-4" style="border-left: 4px solid var(--clr-green); background: var(--clr-surface);">
    <div class="grid grid-4 gap-3" style="font-size: 13px;">
        <div>
            <span class="muted" style="display: block; font-size: 11px; text-transform: uppercase; font-weight: 600;">Statutory Jurisdiction</span>
            <strong>Republic of Kenya</strong>
        </div>
        <div>
            <span class="muted" style="display: block; font-size: 11px; text-transform: uppercase; font-weight: 600;">Enacting Legislation</span>
            <strong>Data Protection Act, 2019</strong>
        </div>
        <div>
            <span class="muted" style="display: block; font-size: 11px; text-transform: uppercase; font-weight: 600;">Regulatory Authority</span>
            <strong>ODPC (Office of the DPC)</strong>
        </div>
        <div>
            <span class="muted" style="display: block; font-size: 11px; text-transform: uppercase; font-weight: 600;">Effective Date</span>
            <strong>January 1, 2026</strong>
        </div>
    </div>
</div>

<div class="grid" style="grid-template-columns: 280px 1fr; gap: 24px; align-items: start;">
    {{-- Sticky Table of Contents --}}
    <div class="card p-3" style="position: sticky; top: 20px; font-size: 13px;">
        <h4 style="font-size: 14px; font-weight: 700; margin: 0 0 12px; padding-bottom: 8px; border-bottom: 1px solid var(--clr-border);">
            Policy Navigation
        </h4>
        <nav style="display: flex; flex-direction: column; gap: 6px;">
            <a href="#sec-preamble" class="muted" style="text-decoration: none; padding: 4px 6px; border-radius: 4px;">1. Statutory Preamble &amp; Scope</a>
            <a href="#sec-roles" class="muted" style="text-decoration: none; padding: 4px 6px; border-radius: 4px;">2. Legal Capacities: Controller vs. Processor</a>
            <a href="#sec-principles" class="muted" style="text-decoration: none; padding: 4px 6px; border-radius: 4px;">3. Principles of Data Protection</a>
            <a href="#sec-data-collected" class="muted" style="text-decoration: none; padding: 4px 6px; border-radius: 4px;">4. Categories of Personal Data Collected</a>
            <a href="#sec-lawful-basis" class="muted" style="text-decoration: none; padding: 4px 6px; border-radius: 4px;">5. Lawful Bases for Processing (Sec. 30)</a>
            <a href="#sec-security" class="muted" style="text-decoration: none; padding: 4px 6px; border-radius: 4px;">6. Security Safeguards &amp; Encryption</a>
            <a href="#sec-mpesa" class="muted" style="text-decoration: none; padding: 4px 6px; border-radius: 4px;">7. M-Pesa &amp; Payment Data Confidentiality</a>
            <a href="#sec-verticals" class="muted" style="text-decoration: none; padding: 4px 6px; border-radius: 4px;">8. Industry-Specific Processing</a>
            <a href="#sec-rights" class="muted" style="text-decoration: none; padding: 4px 6px; border-radius: 4px;">9. Statutory Rights of Data Subjects</a>
            <a href="#sec-retention" class="muted" style="text-decoration: none; padding: 4px 6px; border-radius: 4px;">10. Retention &amp; KRA Tax Alignment</a>
            <a href="#sec-breach" class="muted" style="text-decoration: none; padding: 4px 6px; border-radius: 4px;">11. 72-Hour Breach Protocols (Sec. 43)</a>
            <a href="#sec-dpo" class="muted" style="text-decoration: none; padding: 4px 6px; border-radius: 4px;">12. DPO Contact &amp; ODPC Redress</a>
        </nav>
    </div>

    {{-- Main Legal Document Content --}}
    <div class="card p-5" style="line-height: 1.7; font-size: 14px; color: var(--clr-text);">

        {{-- Section 1 --}}
        <section id="sec-preamble" style="margin-bottom: 36px;">
            <h2 style="font-size: 20px; font-weight: 700; color: var(--clr-text); border-bottom: 2px solid var(--clr-header-tint); padding-bottom: 8px; margin-bottom: 16px;">
                1. Statutory Preamble &amp; Scope
            </h2>
            <p>
                This Privacy Policy is formulated and issued by <strong>BlePOS Technologies Limited</strong> ("BlePOS", "Platform Provider", "we", "us", or "our") in strict accordance with the provisions of the <strong>Data Protection Act, 2019 (Act No. 24 of 2019)</strong> of the Republic of Kenya, read together with the <em>Data Protection (General) Regulations, 2021</em> and the <em>Data Protection (Registration of Data Controllers and Data Processors) Regulations, 2021</em>.
            </p>
            <p>
                This Policy governs the collection, processing, storage, transmission, and protection of personal data belonging to business subscribers ("Tenants"), their authorized branch personnel ("Staff"), and their retail or commercial end-customers ("Data Subjects") utilizing the BlePOS software-as-a-service (SaaS) multi-tenant point-of-sale platform.
            </p>
        </section>

        {{-- Section 2 --}}
        <section id="sec-roles" style="margin-bottom: 36px;">
            <h2 style="font-size: 20px; font-weight: 700; color: var(--clr-text); border-bottom: 2px solid var(--clr-header-tint); padding-bottom: 8px; margin-bottom: 16px;">
                2. Legal Capacities: Data Controller vs. Data Processor
            </h2>
            <p>
                In compliance with Section 2 of the Data Protection Act, 2019, the parties acknowledge and agree to the following statutory legal capacities:
            </p>
            <div style="background: var(--clr-bg); border: 1px solid var(--clr-border); border-radius: 8px; padding: 16px; margin: 16px 0;">
                <p style="margin: 0 0 10px;">
                    <strong>The Tenant (Your Business):</strong> Operates as the <strong>Data Controller</strong>. You determine the lawful purpose and specific operational means of collecting, retaining, and managing personal data regarding your retail customers, patients, farmers, drivers, and employees.
                </p>
                <p style="margin: 0;">
                    <strong>BlePOS Technologies Limited:</strong> Operates strictly as the <strong>Data Processor</strong>. We store, transmit, index, encrypt, and execute automated transactional tasks strictly upon your documented instructions, within the limits of our Master SaaS Agreement.
                </p>
            </div>
            <p>
                BlePOS maintains an independent designation as a Data Controller exclusively with respect to the direct administrative, billing, and credential information of Tenant account holders.
            </p>
        </section>

        {{-- Section 3 --}}
        <section id="sec-principles" style="margin-bottom: 36px;">
            <h2 style="font-size: 20px; font-weight: 700; color: var(--clr-text); border-bottom: 2px solid var(--clr-header-tint); padding-bottom: 8px; margin-bottom: 16px;">
                3. Principles of Data Protection (Section 19)
            </h2>
            <p>
                Every transaction and operational data flow across BlePOS adheres to the core principles enshrined in Section 19 of the Kenya Data Protection Act, 2019:
            </p>
            <ul style="padding-left: 20px;">
                <li><strong>Lawfulness, Fairness &amp; Transparency:</strong> Personal data is processed without deception, with full statutory notices provided to the data subject.</li>
                <li><strong>Purpose Specification:</strong> Data is gathered exclusively for specified, explicit, and legitimate commercial retail and POS accounting purposes.</li>
                <li><strong>Data Minimization:</strong> Only data elements strictly required to execute POS sales, tax compliance, or shift balancing are collected.</li>
                <li><strong>Accuracy:</strong> Tenants and staff are provided self-service facilities to ensure customer records and inventories remain up-to-date and accurate.</li>
                <li><strong>Storage Limitation:</strong> Data is retained only for the period necessary to satisfy business objectives or statutory tax auditing obligations under Kenyan law.</li>
                <li><strong>Integrity, Confidentiality &amp; Security:</strong> Technical and cryptographic measures prevent unauthorized access, accidental alteration, or disclosure.</li>
            </ul>
        </section>

        {{-- Section 4 --}}
        <section id="sec-data-collected" style="margin-bottom: 36px;">
            <h2 style="font-size: 20px; font-weight: 700; color: var(--clr-text); border-bottom: 2px solid var(--clr-header-tint); padding-bottom: 8px; margin-bottom: 16px;">
                4. Categories of Personal Data Collected
            </h2>
            <p>We process the following categories of data in connection with your tenant subscription and daily branch operations:</p>
            <div class="grid grid-2 gap-3" style="margin-top: 14px;">
                <div style="background: var(--clr-bg); border: 1px solid var(--clr-border); border-radius: 8px; padding: 14px;">
                    <strong style="color: var(--clr-blue);">A. Tenant Account &amp; Identity Data</strong>
                    <p style="margin: 6px 0 0; font-size: 13px;" class="muted">
                        Business registration name, KRA PIN, County Business Permit number, registered physical address, director/owner full name, national ID/passport number, primary phone number, and official billing email.
                    </p>
                </div>
                <div style="background: var(--clr-bg); border: 1px solid var(--clr-border); border-radius: 8px; padding: 14px;">
                    <strong style="color: var(--clr-blue);">B. Staff &amp; Cashier Credentials</strong>
                    <p style="margin: 6px 0 0; font-size: 13px;" class="muted">
                        Operator name, phone, branch assignment, system role, hashed passwords (Argon2id/Bcrypt), TOTP secret tokens (encrypted via AES-256), and shift login timestamps.
                    </p>
                </div>
                <div style="background: var(--clr-bg); border: 1px solid var(--clr-border); border-radius: 8px; padding: 14px;">
                    <strong style="color: var(--clr-green);">C. POS Checkout &amp; Transaction Data</strong>
                    <p style="margin: 6px 0 0; font-size: 13px;" class="muted">
                        Itemized product purchases, sale total, VAT breakdown, cashier ID, timestamp, and optional customer receipt recipient details (phone or email).
                    </p>
                </div>
                <div style="background: var(--clr-bg); border: 1px solid var(--clr-border); border-radius: 8px; padding: 14px;">
                    <strong style="color: var(--clr-green);">D. Payment &amp; Mobile Money Data</strong>
                    <p style="margin: 6px 0 0; font-size: 13px;" class="muted">
                        M-Pesa transaction reference codes (e.g. <code>QA12345678</code>), masked MSISDN phone numbers (<code>2547••••1234</code>), amount, and settlement status. <em>Plaintext debit/credit card CVV or PIN numbers are never handled or stored.</em>
                    </p>
                </div>
            </div>
        </section>

        {{-- Section 5 --}}
        <section id="sec-lawful-basis" style="margin-bottom: 36px;">
            <h2 style="font-size: 20px; font-weight: 700; color: var(--clr-text); border-bottom: 2px solid var(--clr-header-tint); padding-bottom: 8px; margin-bottom: 16px;">
                5. Lawful Bases for Processing (Section 30 DPA 2019)
            </h2>
            <p>Under Section 30 of the Kenya Data Protection Act, 2019, processing on BlePOS occurs under the following recognized legal grounds:</p>
            <ol style="padding-left: 20px;">
                <li><strong>Performance of a Contract (Sec. 30(1)(b)):</strong> Necessary to provide point-of-sale functionality, user logins, shift tracking, and generate receipts requested by purchasers.</li>
                <li><strong>Compliance with Legal Obligations (Sec. 30(1)(c)):</strong> Fulfilling mandatory requirements under the <em>Kenya Tax Procedures Act, 2015</em>, the <em>Value Added Tax Act, 2013</em>, and KRA Electronic Tax Invoice Management System (eTIMS) mandates.</li>
                <li><strong>Legitimate Interests (Sec. 30(1)(f)):</strong> Preventing cash leakage, shift fraud, cashier balance discrepancies, and ensuring multi-tenant network security without undermining data subject fundamental rights.</li>
                <li><strong>Explicit Consent (Sec. 30(1)(a)):</strong> Where customer contact information is recorded for promotional SMS announcements or loyalty incentives.</li>
            </ol>
        </section>

        {{-- Section 6 --}}
        <section id="sec-security" style="margin-bottom: 36px;">
            <h2 style="font-size: 20px; font-weight: 700; color: var(--clr-text); border-bottom: 2px solid var(--clr-header-tint); padding-bottom: 8px; margin-bottom: 16px;">
                6. Security Safeguards &amp; Technical Measures (Section 41)
            </h2>
            <p>
                Pursuant to Section 41 of the Data Protection Act, 2019, BlePOS employs state-of-the-art technical, physical, and organizational safeguards:
            </p>
            <ul style="padding-left: 20px;">
                <li><strong>Cryptographic Isolation:</strong> All database queries enforce fail-closed <code>TenantScope</code> and <code>ShopScope</code> filters. Cross-tenant reads or updates are blocked at both application and ORM levels.</li>
                <li><strong>Encryption at Rest:</strong> Sensitive credentials (M-Pesa Consumer Secrets, Passkeys, TOTP secrets) are encrypted using <strong>AES-256-GCM</strong>. Plaintext secrets are never recorded in database fields or system logs.</li>
                <li><strong>Encryption in Transit:</strong> Transport Layer Security (TLS 1.3) with HTTP Strict Transport Security (HSTS), Content Security Policy (CSP), and secure cookies.</li>
                <li><strong>Non-Impersonation Guarantee:</strong> Platform administrators and support personnel are strictly prohibited from logging into or assuming tenant accounts. Diagnostic tools present aggregate integrity counts without viewing customer records.</li>
            </ul>
        </section>

        {{-- Section 7 --}}
        <section id="sec-mpesa" style="margin-bottom: 36px;">
            <h2 style="font-size: 20px; font-weight: 700; color: var(--clr-text); border-bottom: 2px solid var(--clr-header-tint); padding-bottom: 8px; margin-bottom: 16px;">
                7. M-Pesa &amp; Payment Data Confidentiality
            </h2>
            <p>
                In alignment with Central Bank of Kenya (CBK) National Payment System guidelines and Safaricom Daraja API standards:
            </p>
            <ul style="padding-left: 20px;">
                <li>BlePOS serves solely as an automated technical conduit for initiating STK Pushes and recording C2B/B2C transaction receipts.</li>
                <li>Customer payment funds move directly between Safaricom M-Pesa and your designated Paybill or Till shortcode. <strong>BlePOS never holds, deposits, or commingles your transactional sales revenue.</strong></li>
                <li>Callback payloads received from payment gateways are sanitized; sensitive customer phone numbers are redacted in operator logs.</li>
            </ul>
        </section>

        {{-- Section 8 --}}
        <section id="sec-verticals" style="margin-bottom: 36px;">
            <h2 style="font-size: 20px; font-weight: 700; color: var(--clr-text); border-bottom: 2px solid var(--clr-header-tint); padding-bottom: 8px; margin-bottom: 16px;">
                8. Industry-Specific Processing Safeguards
            </h2>
            <p>Where your tenant activates specialized industry modules, the following statutory guidelines apply:</p>
            <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 12px;">
                <div style="background: var(--clr-bg); padding: 12px; border-radius: 6px; font-size: 13px;">
                    <strong>Agrovet Module:</strong> Farmer customer ledgers and village locations are processed for credit management under the Micro and Small Enterprise Act 2012. Agrochemical dispensing records comply with Pest Control Products Board guidelines.
                </div>
                <div style="background: var(--clr-bg); padding: 12px; border-radius: 6px; font-size: 13px;">
                    <strong>Auto Garage Module:</strong> Vehicle registration plates (e.g. <code>KCA 123A</code>) and VIN records are processed solely for maintenance histories and ownership accountability under the Traffic Act (Cap 403).
                </div>
                <div style="background: var(--clr-bg); padding: 12px; border-radius: 6px; font-size: 13px;">
                    <strong>Car Hire &amp; Fleet Module:</strong> National ID and Driving License records collected during vehicle rentals are stored under strict role-gated access to protect against vehicular theft or traffic violation disputes.
                </div>
                <div style="background: var(--clr-bg); padding: 12px; border-radius: 6px; font-size: 13px;">
                    <strong>Gym &amp; Fitness Module:</strong> Member check-in timestamps and medical waiver notes are treated as sensitive personal data under Section 44 of the DPA 2019, accessible solely by the tenant's gym management.
                </div>
            </div>
        </section>

        {{-- Section 9 --}}
        <section id="sec-rights" style="margin-bottom: 36px;">
            <h2 style="font-size: 20px; font-weight: 700; color: var(--clr-text); border-bottom: 2px solid var(--clr-header-tint); padding-bottom: 8px; margin-bottom: 16px;">
                9. Statutory Rights of Data Subjects (Part IV DPA 2019)
            </h2>
            <p>Every natural person whose data is recorded in BlePOS enjoys the following enforceable statutory rights:</p>
            <div class="grid grid-2 gap-3" style="margin-top: 12px;">
                <div style="padding: 10px; border: 1px solid var(--clr-border); border-radius: 6px; font-size: 13px;">
                    <strong>Right to Be Informed (Sec. 26(a)):</strong> To know why their data is collected and how it will be processed.
                </div>
                <div style="padding: 10px; border: 1px solid var(--clr-border); border-radius: 6px; font-size: 13px;">
                    <strong>Right of Access (Sec. 26(b)):</strong> To request a free electronic copy of all personal records held.
                </div>
                <div style="padding: 10px; border: 1px solid var(--clr-border); border-radius: 6px; font-size: 13px;">
                    <strong>Right to Rectification (Sec. 26(c)):</strong> To rectify false, misleading, outdated, or incomplete details.
                </div>
                <div style="padding: 10px; border: 1px solid var(--clr-border); border-radius: 6px; font-size: 13px;">
                    <strong>Right to Erasure (Sec. 26(d)):</strong> To request deletion where data is no longer necessary, subject to tax statutory retention laws.
                </div>
                <div style="padding: 10px; border: 1px solid var(--clr-border); border-radius: 6px; font-size: 13px;">
                    <strong>Right to Data Portability (Sec. 31):</strong> To receive data in an indexed, structured, machine-readable format (CSV/JSON).
                </div>
                <div style="padding: 10px; border: 1px solid var(--clr-border); border-radius: 6px; font-size: 13px;">
                    <strong>Right to Object (Sec. 32):</strong> To object to direct commercial marketing or automated profiling.
                </div>
            </div>
        </section>

        {{-- Section 10 --}}
        <section id="sec-retention" style="margin-bottom: 36px;">
            <h2 style="font-size: 20px; font-weight: 700; color: var(--clr-text); border-bottom: 2px solid var(--clr-header-tint); padding-bottom: 8px; margin-bottom: 16px;">
                10. Data Retention &amp; KRA Tax Alignment
            </h2>
            <p>
                In balancing Section 19(d) of the DPA 2019 with Section 23 of the <em>Kenya Tax Procedures Act, 2015</em>:
            </p>
            <ul style="padding-left: 20px;">
                <li>Sales transactions, receipts, credit notes, and eTIMS tax invoices must be retained for a mandatory statutory period of <strong>five (5) years</strong>.</li>
                <li>Cashier shift logs and employee activity tracking logs are retained for twenty-four (24) months.</li>
                <li>Following the expiration of statutory retention windows, records are subject to certified cryptographic erasure and purge.</li>
            </ul>
        </section>

        {{-- Section 11 --}}
        <section id="sec-breach" style="margin-bottom: 36px;">
            <h2 style="font-size: 20px; font-weight: 700; color: var(--clr-text); border-bottom: 2px solid var(--clr-header-tint); padding-bottom: 8px; margin-bottom: 16px;">
                11. 72-Hour Personal Data Breach Protocols (Section 43)
            </h2>
            <p>
                In the unlikely event of an unauthorized security breach affecting the personal data of Tenant users or customers:
            </p>
            <ol style="padding-left: 20px;">
                <li>BlePOS shall notify the affected Tenant Data Controller without undue delay and in any case within <strong>twenty-four (24) hours</strong> of forensic confirmation.</li>
                <li>BlePOS and the Tenant shall collaborate to file the statutory incident notification with the <strong>Office of the Data Protection Commissioner (ODPC) within seventy-two (72) hours</strong> pursuant to Section 43 of the Act.</li>
                <li>High-risk breaches affecting data subject rights shall trigger immediate SMS or written disclosures instructing protective mitigation steps.</li>
            </ol>
        </section>

        {{-- Section 12 --}}
        <section id="sec-dpo">
            <h2 style="font-size: 20px; font-weight: 700; color: var(--clr-text); border-bottom: 2px solid var(--clr-header-tint); padding-bottom: 8px; margin-bottom: 16px;">
                12. Data Protection Officer (DPO) &amp; ODPC Redress
            </h2>
            <p>
                To exercise any statutory data protection rights, request a copy of your records, or report an incident, contact our designated Data Protection Officer:
            </p>
            <div style="background: var(--clr-bg); border: 1px solid var(--clr-border); border-radius: 8px; padding: 16px; margin: 16px 0;">
                <strong>BlePOS Technologies Limited — Office of Data Protection</strong><br>
                The Mirage, Tower 2, 8th Floor, Chiromo Road, Westlands, Nairobi, Kenya<br>
                Postal Address: P.O. Box 48291 - 00100, Nairobi, Kenya<br>
                Direct DPO Email: <a href="mailto:dpo@blepos.co.ke">dpo@blepos.co.ke</a> &middot; Telephone: +254 700 000 000
            </div>
            <p>
                If you feel your request has not been satisfactorily handled, you possess an unconditional statutory right to lodge an administrative complaint with the supervisory authority:
            </p>
            <div style="background: var(--clr-bg); border: 1px solid var(--clr-border); border-radius: 8px; padding: 16px; margin: 16px 0;">
                <strong>Office of the Data Protection Commissioner (ODPC)</strong><br>
                Britam Tower, 13th Floor, Hospital Road, Upper Hill, Nairobi<br>
                P.O. Box 30920 - 00100, GPO Nairobi, Kenya<br>
                Website: <a href="https://www.odpc.go.ke" target="_blank" rel="noopener">https://www.odpc.go.ke</a> &middot; Email: <a href="mailto:complaints@odpc.go.ke">complaints@odpc.go.ke</a>
            </div>
        </section>

    </div>
</div>
@endsection
