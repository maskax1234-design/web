<x-layout title="Prescriptions">
    <div class="container">
        <x-page-heading
            title="Prescriptions"
            :subtitle="$records->total().' prescriptions issued'"
            :action="route('prescriptions.create')"
            action-label="Add Prescription"
        />

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Prescription No.</th>
                            <th scope="col">Patient</th>
                            <th scope="col">Doctor</th>
                            <th scope="col">Date</th>
                            <th scope="col">Medicines</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $prescription)
                            <tr>
                                <td>{{ $prescription->id }}</td>
                                <td class="font-monospace small">{{ $prescription->prescription_number }}</td>
                                <td class="fw-semibold">
                                    {{ $prescription->patient?->first_name }} {{ $prescription->patient?->last_name }}
                                </td>
                                <td>Dr. {{ $prescription->doctor?->first_name }} {{ $prescription->doctor?->last_name }}</td>
                                <td>{{ $prescription->prescription_date?->format('d M Y') ?? '—' }}</td>
                                <td>{{ $prescription->medicines->count() }}</td>
                                <td><x-badge :status="$prescription->status" /></td>
                                <td class="text-end">
                                    <x-record-actions :route="$route" :record="$prescription" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No prescriptions found.</td>
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