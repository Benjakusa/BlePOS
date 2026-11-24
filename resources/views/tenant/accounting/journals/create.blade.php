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
            <label>
                Entry Date
                <input type="date" name="entry_date" value="{{ old('entry_date', now()->toDateString()) }}" required>
            </label>

            <label>
                Description / Memo
                <input type="text" name="description" value="{{ old('description') }}" placeholder="e.g. Month-end depreciation adjustment" required>
            </label>
        </div>

        <h3 style="font-size: 16px; margin-bottom: 12px;">Journal Entry Lines</h3>
        <table id="linesTable" style="margin-bottom: 20px;">
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
                            <select name="lines[{{ $i }}][account_id]" {{ $i < 2 ? 'required' : '' }}>
                                <option value="">Select Account...</option>
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}" {{ old("lines.{$i}.account_id") == $acc->id ? 'selected' : '' }}>
                                        [{{ $acc->code }}] {{ $acc->name }} ({{ ucfirst($acc->type) }})
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="text" name="lines[{{ $i }}][memo]" value="{{ old("lines.{$i}.memo") }}" placeholder="Line note...">
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" name="lines[{{ $i }}][debit]" value="{{ old("lines.{$i}.debit", '0.00') }}" style="text-align: right;" class="debit-input">
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" name="lines[{{ $i }}][credit]" value="{{ old("lines.{$i}.credit", '0.00') }}" style="text-align: right;" class="credit-input">
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
