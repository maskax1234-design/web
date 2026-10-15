<x-layout title="Patients">
    <div class="container">
        <x-page-heading
            title="Patients"
            :subtitle="$records->total().' patients registered'"
            :action="route('patients.create')"
            action-label="Add Patient"
        />

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Patient No.</th>
                            <th scope="col">Name</th>
                            <th scope="col">Gender</th>
                            <th scope="col">Date of Birth</th>
                            <th scope="col">Phone</th>
                            <th scope="col">Blood Group</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $patient)
                            <tr>
                                <td>{{ $patient->id }}</td>
                                <td class="font-monospace small">{{ $patient->patient_number }}</td>
                                <td class="fw-semibold">{{ $patient->first_name }} {{ $patient->last_name }}</td>
                                <td>{{ $patient->gender }}</td>
                                <td>{{ $patient->date_of_birth?->format('d M Y') ?? '—' }}</td>
                                <td>{{ $patient->phone }}</td>
                                <td>{{ $patient->blood_group ?: '—' }}</td>
                                <td><x-badge :status="$patient->status" /></td>
                                <td class="text-end">
                                    <x-record-actions :route="$route" :record="$patient" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No patients found.</td>
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