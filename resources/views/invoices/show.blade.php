<x-layout :title="$record->invoice_number">
    <div class="container">
        <x-page-heading
            :title="$record->invoice_number"
            :subtitle="'Dated '.($record->invoice_date?->format('d M Y') ?? '—')"
            :action="$record->source === 'PHARMACY_POS' ? null : route('invoices.edit', $record)"
            action-label="Edit"
        />

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">Invoice</div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4 text-muted">Invoice Number</dt>
                            <dd class="col-sm-8 font-monospace">{{ $record->invoice_number }}</dd>

                            <dt class="col-sm-4 text-muted">Patient</dt>
                            <dd class="col-sm-8">
                                @if ($record->patient)
                                    <a href="{{ route('patients.show', $record->patient) }}">
                                        {{ $record->patient->first_name }} {{ $record->patient->last_name }}
                                    </a>
                                @else
                                    —
                                @endif
                            </dd>

                            <dt class="col-sm-4 text-muted">Prescription</dt>
                            <dd class="col-sm-8">
                                @if ($record->prescription)
                                    <a href="{{ route('prescriptions.show', $record->prescription) }}" class="font-monospace">
                                        {{ $record->prescription->prescription_number }}
                                    </a>
                                @else
                                    Not linked
                                @endif
                            </dd>

                            <dt class="col-sm-4 text-muted">Appointment</dt>
                            <dd class="col-sm-8">
                                @if ($record->appointment)
                                    <a href="{{ route('appointments.show', $record->appointment) }}">
                                        {{ $record->appointment->appointment_number }}
                                    </a>
                                @else
                                    Not linked
                                @endif
                            </dd>

                            <dt class="col-sm-4 text-muted">Invoice Date</dt>
                            <dd class="col-sm-8">{{ $record->invoice_date?->format('d M Y') ?? '—' }}</dd>

                            <dt class="col-sm-4 text-muted">Status</dt>
                            <dd class="col-sm-8"><x-badge :status="$record->status" /></dd>

                            <dt class="col-sm-4 text-muted">Notes</dt>
                            <dd class="col-sm-8">{{ $record->notes ?: '—' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                @include('invoices._amounts')
            </div>
        </div>

        <div class="card shadow-sm mt-3">
            <div class="card-header bg-white fw-semibold">Prescription Items</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Medicine</th>
                            <th scope="col" class="text-end">Quantity</th>
                            <th scope="col" class="text-end">Unit Price</th>
                            <th scope="col" class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($record->items as $item)
                            <tr>
                                <td class="fw-semibold">{{ $item->medicine?->name ?? 'Medicine unavailable' }}</td>
                                <td class="text-end">{{ $item->quantity }}</td>
                                <td class="text-end">{{ number_format((float) $item->unit_price, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $item->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No prescription items on this invoice.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex gap-2 mt-3 flex-wrap">
            <a href="{{ route('invoices.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Invoices
            </a>
            <a href="{{ route('invoices.print', $record) }}" class="btn btn-primary">
                <i class="bi bi-printer me-1"></i>Print Invoice
            </a>
        </div>
    </div>
</x-layout>