<x-layout title="Medicines">
    <div class="container">
        <x-page-heading
            title="Medicines"
            :subtitle="$records->total().' medicines in catalogue'"
            :action="route('medicines.create')"
            action-label="Add Medicine"
        />

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Code</th>
                            <th scope="col">Name</th>
                            <th scope="col">Generic</th>
                            <th scope="col">Category</th>
                            <th scope="col">Stock</th>
                            <th scope="col">Unit Price</th>
                            <th scope="col">Expiry</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $medicine)
                            <tr>
                                <td>{{ $medicine->id }}</td>
                                <td class="font-monospace small">{{ $medicine->medicine_code }}</td>
                                <td class="fw-semibold">{{ $medicine->name }}</td>
                                <td class="text-muted">{{ $medicine->generic_name ?: '—' }}</td>
                                <td>{{ $medicine->category ?: '—' }}</td>
                                <td>
                                    <span class="badge {{ $medicine->quantity < 10 ? 'text-bg-danger' : 'text-bg-light border' }}">
                                        {{ $medicine->quantity }}
                                    </span>
                                </td>
                                <td>{{ number_format((float) $medicine->unit_price, 2) }}</td>
                                <td>{{ $medicine->expiry_date?->format('d M Y') ?? '—' }}</td>
                                <td><x-badge :status="$medicine->status" /></td>
                                <td class="text-end">
                                    <x-record-actions :route="$route" :record="$medicine" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">No medicines found.</td>
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