<x-layout :title="'Dr. '.$record->first_name.' '.$record->last_name">
    <div class="container">
        <x-page-heading
            :title="'Dr. '.$record->first_name.' '.$record->last_name"
            :subtitle="$record->specialization.' • '.$record->doctor_number"
            :action="auth()->user()->canAccessModule('doctors') ? route('doctors.edit', $record) : null"
            action-label="Edit"
        />

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">Overview</div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4 text-muted">Doctor Number</dt>
                            <dd class="col-sm-8 font-monospace">{{ $record->doctor_number }}</dd>

                            <dt class="col-sm-4 text-muted">Department</dt>
                            <dd class="col-sm-8">
                                @if ($record->department)
                                    <a href="{{ route('departments.show', $record->department) }}">{{ $record->department->name }}</a>
                                @else
                                    —
                                @endif
                            </dd>

                            <dt class="col-sm-4 text-muted">Specialization</dt>
                            <dd class="col-sm-8">{{ $record->specialization }}</dd>

                            <dt class="col-sm-4 text-muted">Qualification</dt>
                            <dd class="col-sm-8">{{ $record->qualification }}</dd>

                            <dt class="col-sm-4 text-muted">License Number</dt>
                            <dd class="col-sm-8 font-monospace">{{ $record->license_number }}</dd>

                            <dt class="col-sm-4 text-muted">Experience</dt>
                            <dd class="col-sm-8">{{ $record->experience }} years</dd>

                            <dt class="col-sm-4 text-muted">Phone</dt>
                            <dd class="col-sm-8">{{ $record->phone }}</dd>

                            <dt class="col-sm-4 text-muted">Email</dt>
                            <dd class="col-sm-8">{{ $record->email }}</dd>

                            <dt class="col-sm-4 text-muted">Status</dt>
                            <dd class="col-sm-8"><x-badge :status="$record->status" /></dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">Workload</div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-7 text-muted">Appointments</dt>
                            <dd class="col-sm-5">{{ $record->appointments->count() }}</dd>

                            <dt class="col-sm-7 text-muted">Prescriptions</dt>
                            <dd class="col-sm-5">{{ $record->prescriptions->count() }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <a href="{{ route('doctors.index') }}" class="btn btn-secondary mt-3">
            <i class="bi bi-arrow-left me-1"></i>Back to Doctors
        </a>
    </div>
</x-layout>