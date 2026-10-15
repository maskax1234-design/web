<div class="card shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Invoice Summary</div>
    <div class="card-body">
        <table class="table table-sm align-middle mb-0">
            <tbody>
                <tr>
                    <td class="text-muted">Subtotal</td>
                    <td class="text-end">{{ number_format((float) $record->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td class="text-muted">Discount</td>
                    <td class="text-end">− {{ number_format((float) $record->discount, 2) }}</td>
                </tr>
                <tr>
                    <td class="text-muted">Tax ({{ number_format((float) $record->tax_percent, 2) }}%)</td>
                    <td class="text-end">{{ number_format((float) $record->tax, 2) }}</td>
                </tr>
                <tr class="border-top">
                    <th scope="row" class="pt-3 fs-5">Total</th>
                    <td class="text-end pt-3 fs-5 fw-bold">{{ number_format((float) $record->total, 2) }}</td>
                </tr>
                <tr>
                    <td class="text-muted">Paid</td>
                    <td class="text-end text-success">{{ number_format((float) $record->paid_amount, 2) }}</td>
                </tr>
                <tr class="fw-semibold">
                    <td>Balance Due</td>
                    <td class="text-end {{ (float) $record->due_amount > 0 ? 'text-danger' : 'text-success' }}">
                        {{ number_format((float) $record->due_amount, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold">Payments</div>
    <div class="list-group list-group-flush">
        @forelse ($record->payments as $payment)
            <a
                href="{{ route('payments.show', $payment) }}"
                class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
            >
                <span>
                    {{ $payment->payment_number }}
                    <small class="text-muted d-block">{{ $payment->payment_date?->format('d M Y') }}</small>
                </span>
                <span class="fw-semibold">{{ number_format((float) $payment->amount, 2) }}</span>
            </a>
        @empty
            <div class="list-group-item text-muted">No payments recorded.</div>
        @endforelse
    </div>
</div>