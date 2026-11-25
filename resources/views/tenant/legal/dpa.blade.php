@extends('tenant.layout')

@section('title', 'Data Processing Agreement · Kenya DPA 2019')

@section('content')
<div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
            <a href="{{ route('tenant.legal.index') }}" class="muted" style="text-decoration: none; font-size: 13px;">
                <i class="bi bi-arrow-left"></i> Legal Documents
            </a>
            <span class="muted">&bull;</span>
            <span class="badge badge-warning">ODPC Processor Mandate</span>
        </div>
        <h1 class="page-title">Data Processing Agreement (DPA)</h1>
        <p class="muted" style="margin-top: 4px; font-size: 14px;">
            Pursuant to <strong>Section 35 and Section 41</strong> of the Data Protection Act, 2019 of the Republic of Kenya.
        </p>
    </div>
    <div style="display: flex; gap: 10px;">
        <button onclick="window.print()" class="btn btn-secondary">
            <i class="bi bi-printer"></i>
            <span>Print DPA</span>
        </button>
    </div>
</div>

<div class="card p-5" style="line-height: 1.7; font-size: 14px; color: var(--clr-text);">
    <h3>1. Processor Obligations (BlePOS)</h3>
    <p>
        BlePOS acts solely on behalf of the Tenant Data Controller and warrants that:
    </p>
    <ul style="padding-left: 20px;">
        <li>Personal data processed via POS checkout, inventory logging, or customer account creation shall only be processed in accordance with documented instructions from the Tenant.</li>
        <li>Personnel authorized to process personal data have committed themselves to confidentiality or are under an appropriate statutory obligation of confidentiality.</li>
        <li>Appropriate technical and organizational measures (AES-256 encryption, database isolation, audit logs) are maintained to prevent unauthorized or unlawful processing.</li>
        <li>BlePOS shall assist the Tenant in fulfilling its obligations to respond to statutory requests from Data Subjects exercising their rights under Part IV of the Kenya DPA 2019.</li>
    </ul>

    <h3 style="margin-top: 24px;">2. Controller Obligations (Tenant)</h3>
    <p>
        The Tenant warrants that all personal data transferred or entered into the BlePOS system has been collected lawfully, fairly, and transparently, and that data subjects have been appropriately notified in accordance with the law.
    </p>

    <h3 style="margin-top: 24px;">3. Sub-Processors &amp; Hosting Infrastructure</h3>
    <p>
        BlePOS utilizes accredited enterprise cloud infrastructure (with ISO 27001 / SOC 2 certifications) and Safaricom Daraja API for M-Pesa mobile money routing. All sub-processors are bound by data protection obligations no less restrictive than those set forth in this DPA.
    </p>
</div>
@endsection
