@extends('tenant.layout')

@section('title', 'Print Materials')

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h2 class="section-title" style="font-size: 18px;">Print Materials</h2>
            <p class="muted" style="margin-top: 2px;">Raw material stock levels for printing operations</p>
        </div>
        <a href="{{ route('tenant.printing.materials.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i>
            <span>Add Material</span>
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success" style="margin-bottom: 16px;">{{ session('success') }}</div>
    @endif

    <div class="table-container">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Material Name</th>
                        <th>Unit</th>
                        <th>Qty on Hand</th>
                        <th>Reorder Level</th>
                        <th>Cost / Unit</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($materials as $material)
                        @php $isLow = $material->quantity_on_hand <= $material->reorder_level; @endphp
                        <tr @if($isLow) style="background: color-mix(in srgb, var(--color-danger) 8%, transparent);" @endif>
                            <td>{{ $material->name }}</td>
                            <td>{{ $material->unit }}</td>
                            <td>{{ number_format($material->quantity_on_hand, 3) }}</td>
                            <td>{{ number_format($material->reorder_level, 3) }}</td>
                            <td>{{ number_format($material->cost_per_unit, 2) }}</td>
                            <td>
                                @if($isLow)
                                    <span class="badge badge-danger">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        Low Stock
                                    </span>
                                @else
                                    <span class="badge badge-success">OK</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 32px;" class="muted">No materials found. Add your first material to start tracking stock.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top: 16px;">
        {{ $materials->links() }}
    </div>
</div>
@endsection
