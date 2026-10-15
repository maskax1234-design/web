<x-layout :title="'Sale '.$invoice->invoice_number">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div><h1 class="h3 mb-1">Sale {{ $invoice->invoice_number }}</h1><div class="text-muted">{{ $invoice->invoice_date?->format('d M Y') }} · {{ $invoice->cashier?->name ?? 'Cashier unavailable' }}</div></div>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-secondary" href="{{ route('pharmacy.sales.index') }}">Sales history</a>
                @if ($invoice->status === 'UNPAID' && (float) $invoice->paid_amount === 0.0 && $invoice->payments->isEmpty())
                    <a class="btn btn-outline-secondary" href="{{ route('pharmacy.sales.edit', $invoice) }}">Edit sale</a>
                @endif
                <a class="btn btn-primary" href="{{ route('pharmacy.sales.receipt', $invoice) }}"><i class="bi bi-printer me-1"></i>Print receipt</a>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-semibold">Items sold</div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light"><tr><th>Medicine</th><th>Batch(es)</th><th class="text-end">Qty</th><th class="text-end">Unit price</th><th class="text-end">Line total</th></tr></thead>
                            <tbody>
                                @foreach ($invoice->items as $item)
                                    @php
                                        $movements = $invoice->stockMovements->where('invoice_item_id', $item->id)->where('movement_type', 'SALE')->whereNull('reversed_at');
                                    @endphp
                                    <tr>
                                        <td>{{ $item->medicine?->name ?? 'Medicine unavailable' }}<small class="d-block text-muted">{{ $item->medicine?->medicine_code }}</small></td>
                                        <td>{{ $movements->map(fn ($movement) => $movement->batch?->batch_number.' ('.$movement->quantity.')')->filter()->join(', ') ?: '—' }}</td>
                                        <td class="text-end">{{ $item->quantity }}</td>
                                        <td class="text-end">${{ number_format((float) $item->unit_price, 2) }}</td>
                                        <td class="text-end">${{ number_format((float) $item->total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card shadow-sm mt-3">
                    <div class="card-header bg-white fw-semibold">Payment transactions</div>
                    <div class="table-responsive"><table class="table mb-0"><thead class="table-light"><tr><th>Payment ref.</th><th>Date</th><th>Method</th><th class="text-end">Amount</th></tr></thead><tbody>
                        @forelse ($invoice->payments as $payment)
                            <tr><td>{{ $payment->payment_number }}</td><td>{{ $payment->payment_date?->format('d M Y') }}</td><td>{{ $payment->payment_method }}</td><td class="text-end">${{ number_format((float) $payment->amount, 2) }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="text-muted text-center">No payment recorded.</td></tr>
                        @endforelse
                    </tbody></table></div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-semibold">Transaction summary</div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-7 text-muted">Patient</dt><dd class="col-5 text-end">{{ $invoice->patient ? trim($invoice->patient->first_name.' '.$invoice->patient->last_name) : 'Walk-in / OTC' }}</dd>
                            <dt class="col-7 text-muted">Invoice status</dt><dd class="col-5 text-end"><x-badge :status="$invoice->status" /></dd>
                            <dt class="col-7 text-muted">Subtotal</dt><dd class="col-5 text-end">${{ number_format((float) $invoice->subtotal, 2) }}</dd>
                            <dt class="col-7 text-muted">Discount</dt><dd class="col-5 text-end">− ${{ number_format((float) $invoice->discount, 2) }}</dd>
                            <dt class="col-7 text-muted">Tax ({{ number_format((float) $invoice->tax_percent, 2) }}%)</dt><dd class="col-5 text-end">${{ number_format((float) $invoice->tax_amount, 2) }}</dd>
                            <dt class="col-7 border-top pt-2">Grand total</dt><dd class="col-5 text-end border-top pt-2 fw-bold">${{ number_format((float) $invoice->grand_total, 2) }}</dd>
                            <dt class="col-7 text-muted">Amount received</dt><dd class="col-5 text-end">${{ number_format((float) $invoice->payments->sum('amount'), 2) }}</dd>
                            <dt class="col-7 text-muted">Outstanding</dt><dd class="col-5 text-end">${{ number_format((float) $invoice->due_amount, 2) }}</dd>
                        </dl>
                    </div>
                </div>

              
            </div>
        </div>
    </div>
</x-layout>
