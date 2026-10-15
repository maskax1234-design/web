<x-layout title="Doctors">
    <div class="container">
        <x-page-heading
            title="Doctors"
            :subtitle="$records->total().' doctors on staff'"
            :action="auth()->user()->canAccessModule('doctors') ? route('doctors.create') : null"
            action-label="Add Doctor"
        />

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Doctor No.</th>
                            <th scope="col">Name</th>
                            <th scope="col">Specialization</th>
                            <th scope="col">Department</th>
                            <th scope="col">Phone</th>
                            <th scope="col">Experience</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $doctor)
                            <tr>
                                <td>{{ $doctor->id }}</td>
                                <td class="font-monospace small">{{ $doctor->doctor_number }}</td>
                                <td class="fw-semibold">Dr. {{ $doctor->first_name }} {{ $doctor->last_name }}</td>
                                <td>{{ $doctor->specialization }}</td>
                                <td>{{ $doctor->department?->name ?? '—' }}</td>
                                <td>{{ $doctor->phone }}</td>
                                <td>{{ $doctor->experience }} yr{{ $doctor->experience === 1 ? '' : 's' }}</td>
                                <td><x-badge :status="$doctor->status" /></td>
                                <td class="text-end">
                                    <x-record-actions :route="$route" :record="$doctor" :can-manage="auth()->user()->canAccessModule('doctors')" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No doctors found.</td>
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