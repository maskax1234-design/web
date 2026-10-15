<x-layout title="Pharmacy Sales">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1">Pharmacy Sales</h1>
                <p class="text-muted mb-0">View pharmacy POS transactions and their payment status.</p>
            </div>
            <a class="btn btn-primary" href="{{ route('pharmacy.pos') }}"><i class="bi bi-basket me-1"></i>Open POS</a>
        </div>

        <form method="GET" class="card card-body mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-sm-4"><label class="form-label" for="from">From date</label><input class="form-control" type="date" id="from" name="from" value="{{ request('from') }}"></div>
                <div class="col-sm-4"><label class="form-label" for="to">To date</label><input class="form-control" type="date" id="to" name="to" value="{{ request('to') }}"></div>
                <div class="col-sm-4 d-flex gap-2"><button class="btn btn-outline-primary" type="submit">Filter</button><a class="btn btn-outline-secondary" href="{{ route('pharmacy.sales.index') }}">Clear</a></div>
            </div>
        </form>

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Invoice</th><th>Date</th><th>Patient</th><th>Cashier</th><th class="text-end">Total</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($sales as $sale)
                            <tr>
                                <td class="font-monospace">{{ $sale->invoice_number }}</td>
                                <td>{{ $sale->invoice_date?->format('d M Y') ?? '—' }}</td>
                                <td>{{ $sale->patient ? trim($sale->patient->first_name.' '.$sale->patient->last_name) : 'Walk-in / OTC' }}<small class="d-block text-muted">{{ $sale->patient?->patient_number }}</small></td>
                                <td>{{ $sale->cashier?->name ?? '—' }}</td>
                                <td class="text-end">${{ number_format((float) $sale->grand_total, 2) }}</td>
                                <td><x-badge :status="$sale->status" /> @if($sale->pharmacyRefund)<small class="d-block text-muted">Refunded ${{ number_format((float) $sale->pharmacyRefund->amount, 2) }}</small>@endif</td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('pharmacy.sales.show', $sale) }}">Details</a>
                                        @if ($sale->status === 'UNPAID' && (float) $sale->paid_amount === 0.0 && $sale->payments_count === 0)
                                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('pharmacy.sales.edit', $sale) }}">Edit</a>
                                        @endif
                                        @if (auth()->user()?->canAccessModule('pharmacy') && $sale->status !== 'CANCELLED')
                                            <form method="POST" action="{{ route('pharmacy.sales.cancel', $sale) }}" onsubmit="const reason = window.prompt('Enter a reason to void this sale:'); if (reason === null || !reason.trim()) return false; this.elements.reason.value = reason.trim(); return window.confirm('This voids the sale, refunds recorded payments, restores stock, and preserves its audit history. Continue?');">
                                                @csrf
                                                <input type="hidden" name="reason">
                                                <button class="btn btn-sm btn-outline-danger" type="submit" title="Void sale; audit history is retained">Delete / void</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No pharmacy sales found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white">{{ $sales->links() }}</div>
        </div>
    </div>
</x-layout>
