<x-layout title="Appointments">
    <div class="container">
        <x-page-heading
            title="Appointments"
            :subtitle="$records->total().' appointments scheduled'"
            :action="route('appointments.create')"
            action-label="Add Appointment"
        />

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Appointment No.</th>
                            <th scope="col">Patient</th>
                            <th scope="col">Doctor</th>
                            <th scope="col">Department</th>
                            <th scope="col">Date</th>
                            <th scope="col">Time</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $appointment)
                            <tr>
                                <td>{{ $appointment->id }}</td>
                                <td class="font-monospace small">{{ $appointment->appointment_number }}</td>
                                <td class="fw-semibold">
                                    {{ $appointment->patient?->first_name }} {{ $appointment->patient?->last_name }}
                                </td>
                                <td>Dr. {{ $appointment->doctor?->first_name }} {{ $appointment->doctor?->last_name }}</td>
                                <td>{{ $appointment->department?->name ?? '—' }}</td>
                                <td>{{ $appointment->appointment_date?->format('d M Y') ?? '—' }}</td>
                                <td>{{ \Illuminate\Support\Str::substr((string) $appointment->appointment_time, 0, 5) }}</td>
                                <td><x-badge :status="$appointment->status" /></td>
                                <td class="text-end">
                                    <x-record-actions :route="$route" :record="$appointment" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No appointments found.</td>
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