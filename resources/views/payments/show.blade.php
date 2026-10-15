<x-layout :title="$record->payment_number">
    <div class="container">
        <x-page-heading
            :title="$record->payment_number"
            :subtitle="'Received on '.($record->payment_date?->format('d M Y') ?? '—')"
            :action="route('payments.edit', $record)"
            action-label="Edit"
        />

        <div class="card shadow-sm">
            <div class="card-body text-center py-4">
                <div class="text-muted small text-uppercase">Amount Paid</div>
                <div class="display-5 text-success">{{ number_format((float) $record->amount, 2) }}</div>
            </div>
        </div>

        <div class="card shadow-sm mt-3">
            <div class="card-header bg-white fw-semibold">Details</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">Payment Number</dt>
                    <dd class="col-sm-8 font-monospace">{{ $record->payment_number }}</dd>

                    <dt class="col-sm-4 text-muted">Invoice</dt>
                    <dd class="col-sm-8">
                        @if ($record->invoice)
                            <a href="{{ route('invoices.show', $record->invoice) }}">
                                {{ $record->invoice->invoice_number }}
                            </a>
                        @else
                            —
                        @endif
                    </dd>

                    <dt class="col-sm-4 text-muted">Patient</dt>
                    <dd class="col-sm-8">
                        @if ($record->invoice?->patient)
                            <a href="{{ route('patients.show', $record->invoice->patient) }}">
                                {{ $record->invoice->patient->first_name }} {{ $record->invoice->patient->last_name }}
                            </a>
                        @else
                            —
                        @endif
                    </dd>

                    <dt class="col-sm-4 text-muted">Payment Date</dt>
                    <dd class="col-sm-8">{{ $record->payment_date?->format('d M Y') ?? '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Method</dt>
                    <dd class="col-sm-8">{{ $record->payment_method }}</dd>

                    <dt class="col-sm-4 text-muted">Reference</dt>
                    <dd class="col-sm-8">{{ $record->reference ?: '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Received By</dt>
                    <dd class="col-sm-8">{{ $record->receivedBy?->name ?? 'Not recorded' }}</dd>

                    <dt class="col-sm-4 text-muted">Notes</dt>
                    <dd class="col-sm-8">{{ $record->notes ?: '—' }}</dd>
                </dl>
            </div>
        </div>

        <a href="{{ route('payments.index') }}" class="btn btn-secondary mt-3">
            <i class="bi bi-arrow-left me-1"></i>Back to Payments
        </a>
    </div>
</x-layout>