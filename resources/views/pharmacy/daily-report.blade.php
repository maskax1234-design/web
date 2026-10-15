<x-layout title="Daily Pharmacy Report">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div><h1 class="h3 mb-1">Daily Pharmacy Report</h1><p class="text-muted mb-0">Sales, recorded payments and refunds for the selected date.</p></div>
            <a class="btn btn-outline-primary" href="{{ route('pharmacy.pos') }}"><i class="bi bi-basket me-1"></i>Open POS</a>
        </div>
        <form method="GET" class="card card-body mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-sm-4"><label class="form-label" for="date">Report date</label><input class="form-control" id="date" name="date" type="date" value="{{ $date }}" required></div>
                <div class="col-sm-3"><button class="btn btn-primary" type="submit">Run report</button></div>
            </div>
        </form>

        <div class="row g-3 mb-3">
            <div class="col-md-3"><div class="card card-body h-100"><div class="text-muted">Completed sales</div><div class="fs-4 fw-bold">{{ $sales->where('status', '!=', 'CANCELLED')->count() }}</div></div></div>
            <div class="col-md-3"><div class="card card-body h-100"><div class="text-muted">Net billed total</div><div class="fs-4 fw-bold">${{ number_format((float) $grossSales, 2) }}</div></div></div>
            <div class="col-md-3"><div class="card card-body h-100"><div class="text-muted">Payments recorded</div><div class="fs-4 fw-bold">${{ number_format((float) $payments, 2) }}</div></div></div>
            <div class="col-md-3"><div class="card card-body h-100"><div class="text-muted">Refunds issued</div><div class="fs-4 fw-bold text-danger">− ${{ number_format((float) $refunds, 2) }}</div></div></div>
        </div>

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Invoice</th><th>Patient</th><th>Status</th><th class="text-end">Grand total</th><th class="text-end">Paid today</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($sales as $sale)
                            <tr>
                                <td>{{ $sale->invoice_number }}</td>
                                <td>{{ $sale->patient?->first_name }} {{ $sale->patient?->last_name }}</td>
                                <td><x-badge :status="$sale->status" /></td>
                                <td class="text-end">${{ number_format((float) $sale->grand_total, 2) }}</td>
                                <td class="text-end">${{ number_format((float) $sale->paid_today, 2) }}</td>
                                <td class="text-end"><a href="{{ route('pharmacy.sales.show', $sale) }}" class="btn btn-sm btn-outline-primary">Details</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No pharmacy transactions on {{ \Illuminate\Support\Carbon::parse($date)->format('d M Y') }}.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layout>
