<x-layout :title="$record->name">
    <div class="container">
        <x-page-heading
            :title="$record->name"
            subtitle="Department details"
            :action="route('departments.edit', $record)"
            action-label="Edit"
        />

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">Overview</div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4 text-muted">Name</dt>
                            <dd class="col-sm-8">{{ $record->name }}</dd>

                            <dt class="col-sm-4 text-muted">Description</dt>
                            <dd class="col-sm-8">{{ $record->description ?: '—' }}</dd>

                            <dt class="col-sm-4 text-muted">Status</dt>
                            <dd class="col-sm-8"><x-badge :status="$record->status" /></dd>

                            <dt class="col-sm-4 text-muted">Created</dt>
                            <dd class="col-sm-8">{{ $record->created_at?->format('d M Y, H:i') }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">Doctors</div>
                    <div class="list-group list-group-flush">
                        @forelse ($record->doctors as $doctor)
                            <a
                                href="{{ route('doctors.show', $doctor) }}"
                                class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                            >
                                <span>Dr. {{ $doctor->first_name }} {{ $doctor->last_name }}</span>
                                <span class="text-muted small">{{ $doctor->specialization }}</span>
                            </a>
                        @empty
                            <div class="list-group-item text-muted">No doctors assigned.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <a href="{{ route('departments.index') }}" class="btn btn-secondary mt-3">
            <i class="bi bi-arrow-left me-1"></i>Back to Departments
        </a>
    </div>
</x-layout>