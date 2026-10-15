<x-layout title="Payments">
    <div class="container">
        <x-page-heading
            title="Payments"
            :subtitle="$records->total().' payments received'"
            :action="route('payments.create')"
            action-label="Add Payment"
        />

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Payment No.</th>
                            <th scope="col">Invoice</th>
                            <th scope="col">Patient</th>
                            <th scope="col">Amount</th>
                            <th scope="col">Method</th>
                            <th scope="col">Date</th>
                            <th scope="col">Received By</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $payment)
                            <tr>
                                <td>{{ $payment->id }}</td>
                                <td class="font-monospace small">{{ $payment->payment_number }}</td>
                                <td>
                                    <a href="{{ route('invoices.show', $payment->invoice) }}">
                                        {{ $payment->invoice?->invoice_number ?? '—' }}
                                    </a>
                                </td>
                                <td>
                                    {{ $payment->invoice?->patient?->first_name }}
                                    {{ $payment->invoice?->patient?->last_name }}
                                </td>
                                <td class="fw-semibold">{{ number_format((float) $payment->amount, 2) }}</td>
                                <td>{{ $payment->payment_method }}</td>
                                <td>{{ $payment->payment_date?->format('d M Y') ?? '—' }}</td>
                                <td>{{ $payment->receivedBy?->name ?? '—' }}</td>
                                <td class="text-end">
                                    <x-record-actions :route="$route" :record="$payment" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No payments found.</td>
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