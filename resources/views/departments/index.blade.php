<x-layout title="Departments">
    <div class="container">
        <x-page-heading
            title="Departments"
            :subtitle="$records->total().' departments registered'"
            :action="route('departments.create')"
            action-label="Add Department"
        />

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Name</th>
                            <th scope="col">Description</th>
                            <th scope="col">Doctors</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $department)
                            <tr>
                                <td>{{ $department->id }}</td>
                                <td class="fw-semibold">{{ $department->name }}</td>
                                <td class="text-muted">
                                    {{ \Illuminate\Support\Str::limit($department->description, 60) ?: '—' }}
                                </td>
                                <td>{{ $department->doctors->count() }}</td>
                                <td><x-badge :status="$department->status" /></td>
                                <td class="text-end">
                                    <x-record-actions :route="$route" :record="$department" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No departments found.</td>
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