@extends('tenant.layout')

@section('title', 'Expenses')

@section('content')
<header>
    <div>
        <h1>Operating Expenses</h1>
        <div class="muted">Store operational expenditures, utilities, and branch purchases</div>
    </div>
    <div>
        <a href="{{ route('tenant.accounting.expenses.create') }}" class="btn">+ Record Expense</a>
    </div>
</header>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Expense #</th>
                <th>Date</th>
                <th>Category</th>
                <th>Description</th>
                <th>Branch</th>
                <th>Paid Via</th>
                <th style="text-align: right;">Amount (KES)</th>
                <th>Journal Ref</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expenses as $expense)
                <tr>
                    <td><code>{{ $expense->expense_number }}</code></td>
                    <td>{{ $expense->expense_date->format('M d, Y') }}</td>
                    <td><span class="badge badge-secondary">{{ ucfirst($expense->category) }}</span></td>
                    <td><strong>{{ $expense->description }}</strong></td>
                    <td class="muted">{{ $expense->shop->name ?? '—' }}</td>
                    <td><code>{{ strtoupper($expense->payment_method) }}</code></td>
                    <td style="text-align: right; font-weight: 650; font-family: Helvetica, Arial, sans-serif;">
                        {{ number_format((float) $expense->amount, 2) }}
                    </td>
                    <td>
                        @if($expense->journalEntry)
                            <a href="{{ route('tenant.accounting.journals.show', $expense->journalEntry) }}" class="btn btn-secondary btn-sm">
                                #{{ $expense->journalEntry->entry_number }}
                            </a>
                        @else
                            <span class="muted">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="muted" style="text-align: center; padding: 24px;">No expenses recorded yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top: 16px;">
    {{ $expenses->links() }}
</div>
@endsection
