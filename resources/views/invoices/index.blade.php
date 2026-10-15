<x-layout title="Invoices">
    <div class="container">
        <x-page-heading
            title="Invoices"
            :subtitle="$records->total().' invoices raised'"
            :action="route('invoices.create')"
            action-label="Add Invoice"
        />

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Invoice No.</th>
                            <th scope="col">Patient</th>
                            <th scope="col">Date</th>
                            <th scope="col">Total</th>
                            <th scope="col">Paid</th>
                            <th scope="col">Due</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $invoice)
                            <tr>
                                <td>{{ $invoice->id }}</td>
                                <td class="font-monospace small">{{ $invoice->invoice_number }}</td>
                                <td class="fw-semibold">
                                    {{ $invoice->patient?->first_name }} {{ $invoice->patient?->last_name }}
                                </td>
                                <td>{{ $invoice->invoice_date?->format('d M Y') ?? '—' }}</td>
                                <td>{{ number_format((float) $invoice->total, 2) }}</td>
                                <td>{{ number_format((float) $invoice->paid_amount, 2) }}</td>
                                <td class="{{ (float) $invoice->due_amount > 0 ? 'text-danger fw-semibold' : '' }}">
                                    {{ number_format((float) $invoice->due_amount, 2) }}
                                </td>
                                <td><x-badge :status="$invoice->status" /></td>
                                <td class="text-end">
                                    <x-record-actions :route="$route" :record="$invoice" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No invoices found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            {{ $records->links() }}
        </div>
    </div>
</x-layout>