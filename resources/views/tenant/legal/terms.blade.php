@extends('tenant.layout')

@section('title', 'Master Terms of Service · Republic of Kenya')

@section('content')
<div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
            <a href="{{ route('tenant.legal.index') }}" class="muted" style="text-decoration: none; font-size: 13px;">
                <i class="bi bi-arrow-left"></i> Legal Documents
            </a>
            <span class="muted">&bull;</span>
            <span class="badge badge-success">Laws of Kenya Enforceable</span>
            <span class="badge badge-info">Version 2.1 (Active)</span>
        </div>
        <h1 class="page-title">Master SaaS Agreement &amp; Terms of Service</h1>
        <p class="muted" style="margin-top: 4px; font-size: 14px;">
            Governing the commercial use of the BlePOS multi-tenant retail operating system in the <strong>Republic of Kenya</strong>.
        </p>
    </div>
    <div style="display: flex; gap: 10px;">
        <button onclick="window.print()" class="btn btn-secondary">
            <i class="bi bi-printer"></i>
            <span>Print Agreement</span>
        </button>
        <a href="{{ route('tenant.legal.privacy') }}" class="btn btn-outline-primary">
            <span>Privacy Policy</span>
            <i class="bi bi-arrow-right"></i>
        </a>
    </div>
</div>

{{-- Metadata Header Strip --}}
<div class="card p-3 mb-4" style="border-left: 4px solid var(--clr-blue); background: var(--clr-surface);">
    <div class="grid grid-4 gap-3" style="font-size: 13px;">
        <div>
            <span class="muted" style="display: block; font-size: 11px; text-transform: uppercase; font-weight: 600;">Governing Law</span>
            <strong>Laws of Kenya</strong>
        </div>
        <div>
            <span class="muted" style="display: block; font-size: 11px; text-transform: uppercase; font-weight: 600;">Electronic Contracts</span>
            <strong>KICA Cap 411A Compliant</strong>
        </div>
        <div>
            <span class="muted" style="display: block; font-size: 11px; text-transform: uppercase; font-weight: 600;">Dispute Forum</span>
            <strong>Nairobi, Kenya (CIArb / High Court)</strong>
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
            Terms Navigation
        </h4>
        <nav style="display: flex; flex-direction: column; gap: 6px;">
            <a href="#sec-formation" class="muted" style="text-decoration: none; padding: 4px 6px; border-radius: 4px;">1. Electronic Contract Formation</a>
            <a href="#sec-eligibility" class="muted" style="text-decoration: none; padding: 4px 6px; border-radius: 4px;">2. Eligibility &amp; Business KYC</a>
            <a href="#sec-services" class="muted" style="text-decoration: none; padding: 4px 6px; border-radius: 4px;">3. Service Scope &amp; Availability</a>
            <a href="#sec-fees" class="muted" style="text-decoration: none; padding: 4px 6px; border-radius: 4px;">4. Fees, Billing &amp; 16% VAT</a>
            <a href="#sec-payments" class="muted" style="text-decoration: none; padding: 4px 6px; border-radius: 4px;">5. M-Pesa &amp; Payment Processing</a>
            <a href="#sec-etims" class="muted" style="text-decoration: none; padding: 4px 6px; border-radius: 4px;">6. KRA eTIMS Tax Compliance</a>
            <a href="#sec-use" class="muted" style="text-decoration: none; padding: 4px 6px; border-radius: 4px;">7. Acceptable Use &amp; Anti-Illicit Trade</a>
            <a href="#sec-ip" class="muted" style="text-decoration: none; padding: 4px 6px; border-radius: 4px;">8. Intellectual Property &amp; Data</a>
            <a href="#sec-liability" class="muted" style="text-decoration: none; padding: 4px 6px; border-radius: 4px;">9. Limitation of Liability</a>
            <a href="#sec-suspension" class="muted" style="text-decoration: none; padding: 4px 6px; border-radius: 4px;">10. Suspension &amp; Data Retrieval</a>
            <a href="#sec-disputes" class="muted" style="text-decoration: none; padding: 4px 6px; border-radius: 4px;">11. Dispute Resolution &amp; Arbitration</a>
            <a href="#sec-jurisdiction" class="muted" style="text-decoration: none; padding: 4px 6px; border-radius: 4px;">12. Jurisdiction &amp; Miscellaneous</a>
        </nav>
    </div>

    {{-- Main Legal Document Content --}}
    <div class="card p-5" style="line-height: 1.7; font-size: 14px; color: var(--clr-text);">

        {{-- Section 1 --}}
        <section id="sec-formation" style="margin-bottom: 36px;">
            <h2 style="font-size: 20px; font-weight: 700; color: var(--clr-text); border-bottom: 2px solid var(--clr-header-tint); padding-bottom: 8px; margin-bottom: 16px;">
                1. Electronic Contract Formation &amp; Acceptance
            </h2>
            <p>
                This Master Software-as-a-Service (SaaS) Agreement ("Agreement") constitutes a legally valid, binding, and enforceable contract entered into between <strong>BlePOS Technologies Limited</strong> ("BlePOS", "Provider", "we", "us") and the business entity or commercial enterprise identified during workspace registration ("Tenant", "Subscriber", "you").
            </p>
            <p>
                In accordance with <strong>Section 83B of the Kenya Information and Communications Act (KICA), 1998 (Cap 411A)</strong>, electronic execution, electronic click-through acceptance, or continuing use of the BlePOS platform signifies your full, voluntary, and unreserved assent to these Terms. If you do not accept these Terms in their entirety, you must cease all access immediately.
            </p>
        </section>

        {{-- Section 2 --}}
        <section id="sec-eligibility" style="margin-bottom: 36px;">
            <h2 style="font-size: 20px; font-weight: 700; color: var(--clr-text); border-bottom: 2px solid var(--clr-header-tint); padding-bottom: 8px; margin-bottom: 16px;">
                2. Eligibility, Registration &amp; Business KYC
            </h2>
            <p>To subscribe and operate an active BlePOS workspace, you warrant and represent that:</p>
            <ul style="padding-left: 20px;">
                <li>You are an entity duly incorporated, registered, or carrying on lawful business under the laws of the Republic of Kenya (e.g. <em>Companies Act 2015</em>, <em>Registration of Business Names Act Cap 499</em>).</li>
                <li>You hold a valid <strong>Kenya Revenue Authority (KRA) Personal Identification Number (PIN)</strong> and where applicable, a Single Business Permit issued by the relevant County Government.</li>
                <li>The individual registering the account possesses the full legal authority to bind the entity.</li>
                <li>You are solely responsible for safeguarding all administrative passwords and mandatory Time-Based One-Time Password (TOTP) Multi-Factor Authentication keys configured for your workspace.</li>
            </ul>
        </section>

        {{-- Section 3 --}}
        <section id="sec-services" style="margin-bottom: 36px;">
            <h2 style="font-size: 20px; font-weight: 700; color: var(--clr-text); border-bottom: 2px solid var(--clr-header-tint); padding-bottom: 8px; margin-bottom: 16px;">
                3. Service Scope, Availability &amp; Multi-Branch Support
            </h2>
            <p>
                BlePOS provides cloud-hosted retail operating software featuring point-of-sale checkout, inventory tracking, cashier shift balancing, double-entry financial ledgers, and specialized industry vertical modules.
            </p>
            <div style="background: var(--clr-bg); border: 1px solid var(--clr-border); border-radius: 8px; padding: 16px; margin: 16px 0;">
                <h4 style="margin: 0 0 8px; font-size: 15px; font-weight: 700;">Service Level Commitment</h4>
                <p style="margin: 0; font-size: 13px;" class="muted">
                    We warrant a <strong>99.9% platform availability target</strong> across each calendar month, excluding emergency maintenance or scheduled updates conducted during low-traffic hours (01:00 to 04:00 EAT) with at least forty-eight (48) hours advance notice.
                </p>
            </div>
            <p>
                BlePOS performs automated daily database backups with cryptographically verified SHA-256 integrity checksums to ensure operational disaster recovery readiness.
            </p>
        </section>

        {{-- Section 4 --}}
        <section id="sec-fees" style="margin-bottom: 36px;">
            <h2 style="font-size: 20px; font-weight: 700; color: var(--clr-text); border-bottom: 2px solid var(--clr-header-tint); padding-bottom: 8px; margin-bottom: 16px;">
                4. Fees, Billing &amp; 16% Value Added Tax (VAT)
            </h2>
            <p>
                Subscription fees are denominated in <strong>Kenya Shillings (KES)</strong> and billed in advance on a recurring monthly or annual basis:
            </p>
            <ul style="padding-left: 20px;">
                <li><strong>Tax Invoicing:</strong> All subscription charges are subject to statutory <strong>16% Value Added Tax (VAT)</strong> pursuant to the <em>Kenya Value Added Tax Act, 2013</em>. Official electronic tax invoices reflecting BlePOS PIN and your company PIN are accessible directly from your tenant dashboard.</li>
                <li><strong>Payment Methods:</strong> Fees may be remitted via Safaricom M-Pesa Till / Paybill, direct bank clearing via Pesalink / RTGS, or approved enterprise payment mandates.</li>
                <li><strong>Overdue Invoices:</strong> Accounts with undisputed balances overdue by more than seven (7) business days are subject to automated system notifications, followed by temporary POS feature suspension if unresolved after fourteen (14) days.</li>
            </ul>
        </section>

        {{-- Section 5 --}}
        <section id="sec-payments" style="margin-bottom: 36px;">
            <h2 style="font-size: 20px; font-weight: 700; color: var(--clr-text); border-bottom: 2px solid var(--clr-header-tint); padding-bottom: 8px; margin-bottom: 16px;">
                5. M-Pesa Integration &amp; National Payment System Compliance
            </h2>
            <p>
                Where you configure the M-Pesa Credential Vault on your tenant workspace:
            </p>
            <ul style="padding-left: 20px;">
                <li>You warrant that you are the lawful owner or authorized representative of the Safaricom Daraja Shortcode (Paybill or Buy Goods Till Number) configured.</li>
                <li>All customer transactions settled via M-Pesa flow directly from the customer's wallet into your designated Safaricom merchant settlement utility. <strong>BlePOS is not a payment intermediary, depository, or money remitter under the Central Bank of Kenya (CBK) National Payment System Act (Cap 491B).</strong></li>
                <li>Your API Consumer Secret, Passkey, and B2C credentials are encrypted at rest using AES-256 and decrypted solely in ephemeral memory during payment initiation. BlePOS employees possess zero plaintext access to your credentials.</li>
            </ul>
        </section>

        {{-- Section 6 --}}
        <section id="sec-etims" style="margin-bottom: 36px;">
            <h2 style="font-size: 20px; font-weight: 700; color: var(--clr-text); border-bottom: 2px solid var(--clr-header-tint); padding-bottom: 8px; margin-bottom: 16px;">
                6. Kenya Revenue Authority (KRA) eTIMS Compliance
            </h2>
            <p>
                In alignment with the <em>Tax Procedures (Electronic Tax Invoice) Regulations, 2024</em>:
            </p>
            <ul style="padding-left: 20px;">
                <li>The Tenant bears the exclusive statutory obligation to declare accurate product classifications, standard 16% VAT, zero-rated, and exempt items sold through its terminals.</li>
                <li>Tampering with receipt numbers, voiding transactions to artificially suppress tax liability, or intentionally altering electronic audit trails is strictly prohibited and constitutes an immediate violation of Kenyan tax statutes.</li>
            </ul>
        </section>

        {{-- Section 7 --}}
        <section id="sec-use" style="margin-bottom: 36px;">
            <h2 style="font-size: 20px; font-weight: 700; color: var(--clr-text); border-bottom: 2px solid var(--clr-header-tint); padding-bottom: 8px; margin-bottom: 16px;">
                7. Acceptable Use &amp; Anti-Illicit Trade Policy
            </h2>
            <p>You agree not to utilize the platform in furtherance of any unlawful, fraudulent, or restricted enterprise, including:</p>
            <ul style="padding-left: 20px;">
                <li>Trading in counterfeit, substandard, or uninspected goods in violation of the <strong>Anti-Counterfeit Act, 2008</strong> and Kenya Bureau of Standards (KEBS) mandates.</li>
                <li>Unregistered pharmaceutical dispensing or unlicensed distribution of controlled substances under the <strong>Pharmacy and Poisons Act (Cap 244)</strong>.</li>
                <li>Selling banned or restricted agricultural chemicals in violation of the <strong>Pest Control Products Act (Cap 346)</strong>.</li>
                <li>Facilitating money laundering or terrorist financing in violation of the <strong>Proceeds of Crime and Anti-Money Laundering Act (POCAMLA), 2009</strong>.</li>
            </ul>
            <p>BlePOS reserves the statutory right to immediately suspend any workspace found to be participating in criminal or illicit commercial trade.</p>
        </section>

        {{-- Section 8 --}}
        <section id="sec-ip" style="margin-bottom: 36px;">
            <h2 style="font-size: 20px; font-weight: 700; color: var(--clr-text); border-bottom: 2px solid var(--clr-header-tint); padding-bottom: 8px; margin-bottom: 16px;">
                8. Intellectual Property &amp; Ownership of Business Data
            </h2>
            <div style="background: var(--clr-bg); border: 1px solid var(--clr-border); border-radius: 8px; padding: 16px; margin: 16px 0;">
                <p style="margin: 0 0 10px;">
                    <strong>Your Data Remains Exclusively Yours:</strong> As between you and BlePOS, you retain sole and exclusive title, copyright, and ownership over all catalogues, sales ledgers, inventory records, employee names, and customer lists created within your workspace.
                </p>
                <p style="margin: 0;">
                    <strong>BlePOS Proprietary Software:</strong> BlePOS retains all rights, title, patent, trademark, and copyright in the application software, user interface design system, algorithms, APIs, and associated documentation.
                </p>
            </div>
        </section>

        {{-- Section 9 --}}
        <section id="sec-liability" style="margin-bottom: 36px;">
            <h2 style="font-size: 20px; font-weight: 700; color: var(--clr-text); border-bottom: 2px solid var(--clr-header-tint); padding-bottom: 8px; margin-bottom: 16px;">
                9. Limitation of Liability &amp; Disclaimers
            </h2>
            <p>
                To the fullest extent permissible under the <strong>Kenya Consumer Protection Act, 2012</strong> and common law principles:
            </p>
            <ul style="padding-left: 20px;">
                <li>BlePOS provides the software on an "as is" and "as available" basis. While we maintain 99.9% uptime, we do not warrant that operation will be entirely uninterrupted by third-party telecommunication failures (e.g. Safaricom nationwide fiber cuts or cellular gateway latency).</li>
                <li>In no event shall BlePOS be liable for indirect, incidental, consequential, special, or punitive damages, including loss of anticipated profits or business interruption.</li>
                <li>BlePOS's total aggregate liability arising out of or related to this Agreement shall be limited to the total subscription fees actually paid by the Tenant in the <strong>three (3) months preceding the incident</strong>.</li>
            </ul>
        </section>

        {{-- Section 10 --}}
        <section id="sec-suspension" style="margin-bottom: 36px;">
            <h2 style="font-size: 20px; font-weight: 700; color: var(--clr-text); border-bottom: 2px solid var(--clr-header-tint); padding-bottom: 8px; margin-bottom: 16px;">
                10. Termination, Cancellation &amp; Data Retrieval Guarantee
            </h2>
            <p>
                Either party may terminate this Agreement without cause by giving thirty (30) days written notice:
            </p>
            <ul style="padding-left: 20px;">
                <li><strong>Statutory 90-Day Retrieval Window:</strong> Following subscription expiration or cancellation, BlePOS guarantees that your business records and sales history shall be preserved in read-only / exportable format for a minimum of <strong>ninety (90) days</strong>.</li>
                <li>Tenants may export complete transactional, product, customer, and journal ledgers in structured CSV/JSON format at any time prior to account decommissioning.</li>
            </ul>
        </section>

        {{-- Section 11 --}}
        <section id="sec-disputes" style="margin-bottom: 36px;">
            <h2 style="font-size: 20px; font-weight: 700; color: var(--clr-text); border-bottom: 2px solid var(--clr-header-tint); padding-bottom: 8px; margin-bottom: 16px;">
                11. Amicable Conciliation &amp; Arbitration
            </h2>
            <p>
                Any dispute, controversy, or claim arising out of or relating to this Agreement shall be resolved through the following mandatory multi-tiered dispute resolution mechanism:
            </p>
            <ol style="padding-left: 20px;">
                <li><strong>Tier 1 — Good Faith Conciliation:</strong> The parties shall first attempt to resolve the dispute amicably through informal executive negotiations within fourteen (14) business days.</li>
                <li><strong>Tier 2 — Binding Arbitration:</strong> If unresolved, the dispute shall be referred to and finally resolved by arbitration in Nairobi in accordance with the <strong>Arbitration Act, 1995 (Act No. 4 of 1995)</strong> of Kenya. The arbitral tribunal shall consist of a sole arbitrator appointed by mutual agreement, or in default, by the Chairman of the <em>Chartered Institute of Arbitrators (Kenya Branch)</em>. The language of arbitration shall be English.</li>
            </ol>
        </section>

        {{-- Section 12 --}}
        <section id="sec-jurisdiction">
            <h2 style="font-size: 20px; font-weight: 700; color: var(--clr-text); border-bottom: 2px solid var(--clr-header-tint); padding-bottom: 8px; margin-bottom: 16px;">
                12. Governing Law, Jurisdiction &amp; Notices
            </h2>
            <p>
                This Agreement shall be governed by, construed, and interpreted in accordance with the <strong>Laws of the Republic of Kenya</strong>. Subject to the arbitration clause above, the parties irrevocably submit to the non-exclusive jurisdiction of the <strong>High Court of Kenya (Commercial and Tax Division) in Nairobi</strong>.
            </p>
            <div style="background: var(--clr-bg); border: 1px solid var(--clr-border); border-radius: 8px; padding: 16px; margin: 16px 0;">
                <strong>Official Legal Notice Delivery Address:</strong><br>
                Legal Department, BlePOS Technologies Limited<br>
                The Mirage, Tower 2, Chiromo Road, Westlands, Nairobi, Kenya<br>
                Email: <a href="mailto:legal@blepos.co.ke">legal@blepos.co.ke</a>
            </div>
        </section>

    </div>
</div>
@endsection
