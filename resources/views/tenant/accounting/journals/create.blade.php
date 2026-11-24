@extends('tenant.layout')

@section('title', 'New Journal Entry')

@section('content')
<header>
    <div>
        <h1>Record Manual Journal Entry</h1>
        <div class="muted">Post balanced debits and credits directly to the general ledger</div>
    </div>
    <div>
        <a href="{{ route('tenant.accounting.journals.index') }}" class="btn btn-secondary">← Back to Entries</a>
    </div>
</header>

<div class="panel">
    <form method="POST" action="{{ route('tenant.accounting.journals.store') }}" id="journalForm">
        @csrf

        <div style="display: grid; grid-template-columns: 200px 1fr; gap: 16px; margin-bottom: 24px;">
            <x-input type="date" name="entry_date" label="Entry Date" :value="now()->toDateString()" required />
            <x-input name="description" label="Description / Memo" placeholder="e.g. Month-end depreciation adjustment" required />
        </div>

        <h3 style="font-size: 16px; margin-bottom: 12px;">Journal Entry Lines</h3>
        <table id="linesTable" style="margin-bottom: 20px; width: 100%;">
            <thead>
                <tr>
                    <th style="width: 40%;">Account</th>
                    <th style="width: 25%;">Memo</th>
                    <th style="width: 15%; text-align: right;">Debit</th>
                    <th style="width: 15%; text-align: right;">Credit</th>
                    <th style="width: 5%;"></th>
                </tr>
            </thead>
            <tbody>
                @for($i = 0; $i < 4; $i++)
                    <tr>
                        <td>
                            <x-input type="select" name="lines[{{ $i }}][account_id]" :compact="true" :required="$i < 2">
                                <option value="">Select Account...</option>
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}" {{ old("lines.{$i}.account_id") == $acc->id ? 'selected' : '' }}>
                                        [{{ $acc->code }}] {{ $acc->name }} ({{ ucfirst($acc->type) }})
                                    </option>
                                @endforeach
                            </x-input>
                        </td>
                        <td>
                            <x-input name="lines[{{ $i }}][memo]" :compact="true" placeholder="Line note..." />
                        </td>
                        <td>
                            <x-input type="number" step="0.01" min="0" name="lines[{{ $i }}][debit]" :compact="true" class="debit-input" value="0.00" />
                        </td>
                        <td>
                            <x-input type="number" step="0.01" min="0" name="lines[{{ $i }}][credit]" :compact="true" class="credit-input" value="0.00" />
                        </td>
                        <td></td>
                    </tr>
                @endfor
            </tbody>
        </table>

        <div style="display: flex; gap: 12px; align-items: center;">
            <button type="submit" class="btn">Post Journal Entry</button>
            <a href="{{ route('tenant.accounting.journals.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
