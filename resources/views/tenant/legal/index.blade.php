@extends('tenant.layout')

@section('title', 'Legal & Compliance Documents')

@section('content')
<div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <h1 class="page-title">Legal &amp; Regulatory Compliance</h1>
            <span class="badge badge-success" style="display: inline-flex; align-items: center; gap: 6px;">
                <i class="bi bi-shield-check" aria-hidden="true"></i>
                <span>Kenya DPA 2019 Conforming</span>
            </span>
        </div>
        <p class="muted" style="margin-top: 6px; font-size: 14px;">
            Statutory legal agreements, privacy policies, data controller terms, and service level commitments for <strong>{{ $tenant->name ?? 'Tenant Workspace' }}</strong>.
        </p>
    </div>
    <div style="display: flex; gap: 10px;">
        <button onclick="window.print()" class="btn btn-secondary">
            <i class="bi bi-printer"></i>
            <span>Print Records</span>
        </button>
    </div>
</div>

{{-- Statutory Badges & Highlights --}}
<div class="card p-3 mb-4" style="background: linear-gradient(135deg, rgba(22, 163, 74, 0.06), rgba(37, 99, 235, 0.06)); border: 1px solid var(--clr-border);">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 44px; height: 44px; border-radius: 50%; background: var(--clr-surface); display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow-sm); font-size: 20px; color: var(--clr-green);">
                <i class="bi bi-patch-check-fill"></i>
            </div>
            <div>
                <h4 style="margin: 0; font-size: 15px; font-weight: 700;">Republic of Kenya Regulatory Governance</h4>
                <p class="muted" style="margin: 2px 0 0; font-size: 13px;">
                    Compliant with the <strong>Data Protection Act, 2019</strong> (ODPC), <strong>Kenya Information and Communications Act</strong> (KICA Cap 411A), <strong>Consumer Protection Act 2012</strong>, and <strong>KRA eTIMS Tax Procedures Act 2015</strong>.
                </p>
            </div>
        </div>
        <div style="display: flex; gap: 8px;">
            <span class="badge badge-outline">ODPC Data Processor Registered</span>
            <span class="badge badge-outline">5-Year KRA Audit Trail</span>
        </div>
    </div>
</div>

{{-- Document Cards Grid --}}
<div class="grid grid-2 gap-4 mb-4">
    {{-- Privacy Policy Card --}}
    <div class="card p-4" style="display: flex; flex-direction: column; justify-content: space-between; position: relative;">
        <div>
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(37, 99, 235, 0.1); color: var(--clr-blue); display: flex; align-items: center; justify-content: center; font-size: 20px;">
                    <i class="bi bi-shield-lock"></i>
                </div>
                <span class="badge badge-info">Statutory Baseline</span>
            </div>
            <h3 style="font-size: 18px; margin: 0 0 8px; font-weight: 700;">Privacy Policy</h3>
            <p class="muted" style="font-size: 13px; line-height: 1.5; margin-bottom: 16px;">
                Governs how personal data of your retail clients, patients, farmers, and store staff is processed under Section 30 and Part IV of the Kenya Data Protection Act, 2019. Details your statutory rights to access, rectification, erasure, and DPO redress.
            </p>
            <ul style="font-size: 12px; color: var(--clr-muted); padding-left: 18px; margin-bottom: 20px; line-height: 1.6;">
                <li>Designation: Tenant as <strong>Data Controller</strong> &middot; BlePOS as <strong>Data Processor</strong></li>
                <li>Lawful bases for POS checkout and M-Pesa receipts</li>
                <li>AES-256 tenant data isolation &amp; 72-hour breach notice to ODPC</li>
                <li>DPO Contact: <code>dpo@blepos.co.ke</code> &middot; Nairobi, Kenya</li>
            </ul>
        </div>
        <div>
            <a href="{{ route('tenant.legal.privacy') }}" class="btn btn-primary" style="width: 100%; justify-content: center;">
                <i class="bi bi-file-earmark-text"></i>
                <span>Read Full Privacy Policy</span>
            </a>
        </div>
    </div>

    {{-- Terms of Service Card --}}
    <div class="card p-4" style="display: flex; flex-direction: column; justify-content: space-between; position: relative;">
        <div>
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(22, 163, 74, 0.1); color: var(--clr-green); display: flex; align-items: center; justify-content: center; font-size: 20px;">
                    <i class="bi bi-file-earmark-ruled"></i>
                </div>
                <span class="badge badge-success">Enforceable Agreement</span>
            </div>
            <h3 style="font-size: 18px; margin: 0 0 8px; font-weight: 700;">Terms of Service &amp; Master SaaS Agreement</h3>
            <p class="muted" style="font-size: 13px; line-height: 1.5; margin-bottom: 16px;">
                The binding legal contract governing the subscription, software availability, shift management, POS checkouts, M-Pesa credential vaults, and eTIMS tax compliance for your business enterprise.
            </p>
            <ul style="font-size: 12px; color: var(--clr-muted); padding-left: 18px; margin-bottom: 20px; line-height: 1.6;">
                <li>Governed under the Laws of the Republic of Kenya (KICA Cap 411A)</li>
                <li>Fees in Kenya Shillings (KES) inclusive of 16% Statutory VAT</li>
                <li>M-Pesa Till &amp; Paybill direct settlements &amp; zero credential logging</li>
                <li>Dispute escalation: CIArb Kenya Arbitration &middot; High Court of Kenya</li>
            </ul>
        </div>
        <div>
            <a href="{{ route('tenant.legal.terms') }}" class="btn btn-primary" style="width: 100%; justify-content: center;">
                <i class="bi bi-file-earmark-text"></i>
                <span>Read Full Terms of Service</span>
            </a>
        </div>
    </div>

    {{-- Data Processing Agreement Card --}}
    <div class="card p-4" style="display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(234, 179, 8, 0.1); color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                    <i class="bi bi-cpu"></i>
                </div>
                <span class="badge badge-warning">Processor Mandate</span>
            </div>
            <h3 style="font-size: 18px; margin: 0 0 8px; font-weight: 700;">Data Processing Agreement (DPA)</h3>
            <p class="muted" style="font-size: 13px; line-height: 1.5; margin-bottom: 16px;">
                Defines technical obligations between BlePOS as Data Processor and your business as Data Controller. Ensures zero cross-tenant leakage, encrypted storage, and audit logs.
            </p>
        </div>
        <div>
            <a href="{{ route('tenant.legal.dpa') }}" class="btn btn-secondary" style="width: 100%; justify-content: center;">
                <span>View DPA Obligations</span>
            </a>
        </div>
    </div>

    {{-- Service Level Agreement Card --}}
    <div class="card p-4" style="display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(99, 102, 241, 0.1); color: #6366f1; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                    <i class="bi bi-speedometer2"></i>
                </div>
                <span class="badge">99.9% Availability</span>
            </div>
            <h3 style="font-size: 18px; margin: 0 0 8px; font-weight: 700;">Service Level Agreement (SLA)</h3>
            <p class="muted" style="font-size: 13px; line-height: 1.5; margin-bottom: 16px;">
                Guarantees 99.9% uptime, daily automated database snapshots with SHA-256 integrity checks, and priority support response times for payment-critical POS incidents.
            </p>
        </div>
        <div>
            <a href="{{ route('tenant.legal.show', 'sla-policy') }}" class="btn btn-secondary" style="width: 100%; justify-content: center;">
                <span>View SLA Guarantees</span>
            </a>
        </div>
    </div>
</div>

{{-- Contact & Office of Data Protection Commissioner Info --}}
<div class="card p-4">
    <h4 style="margin: 0 0 8px; font-size: 16px; font-weight: 700;">Data Protection Regulatory Oversight</h4>
    <p class="muted" style="font-size: 13px; margin-bottom: 16px;">
        If you have any questions regarding your data protection rights, statutory disclosures, or would like to submit a Data Subject Access Request (DSAR) under the Kenya DPA 2019, please reach out to our legal officer or the regulatory authority:
    </p>
    <div class="grid grid-2 gap-4">
        <div style="padding: 12px; border-radius: 8px; background: var(--clr-bg); border: 1px solid var(--clr-border); font-size: 13px;">
            <strong>BlePOS Technologies Limited — Data Protection Officer</strong><br>
            <span class="muted">Office:</span> The Mirage, Tower 2, Chiromo Road, Westlands, Nairobi, Kenya<br>
            <span class="muted">Email:</span> <a href="mailto:dpo@blepos.co.ke">dpo@blepos.co.ke</a> &middot; <a href="mailto:privacy@blepos.co.ke">privacy@blepos.co.ke</a><br>
            <span class="muted">Response Time:</span> Within 48 business hours
        </div>
        <div style="padding: 12px; border-radius: 8px; background: var(--clr-bg); border: 1px solid var(--clr-border); font-size: 13px;">
            <strong>Office of the Data Protection Commissioner (ODPC)</strong><br>
            <span class="muted">Address:</span> Britam Tower, 13th Floor, Hospital Road, Upper Hill, P.O. Box 30920-00100, Nairobi<br>
            <span class="muted">Portal:</span> <a href="https://www.odpc.go.ke" target="_blank" rel="noopener">www.odpc.go.ke</a><br>
            <span class="muted">Complaints:</span> <a href="mailto:complaints@odpc.go.ke">complaints@odpc.go.ke</a>
        </div>
    </div>
</div>
@endsection
