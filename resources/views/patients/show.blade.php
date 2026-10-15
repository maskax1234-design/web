<x-layout :title="$record->first_name.' '.$record->last_name">
    <div class="container">
        <x-page-heading
            :title="$record->first_name.' '.$record->last_name"
            :subtitle="'Patient No. '.$record->patient_number"
            :action="route('patients.edit', $record)"
            action-label="Edit"
        />

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">Demographics</div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4 text-muted">Patient Number</dt>
                            <dd class="col-sm-8 font-monospace">{{ $record->patient_number }}</dd>

                            <dt class="col-sm-4 text-muted">Gender</dt>
                            <dd class="col-sm-8">{{ $record->gender }}</dd>

                            <dt class="col-sm-4 text-muted">Date of Birth</dt>
                            <dd class="col-sm-8">{{ $record->date_of_birth?->format('d M Y') ?? '—' }}</dd>

                            <dt class="col-sm-4 text-muted">Blood Group</dt>
                            <dd class="col-sm-8">{{ $record->blood_group ?: '—' }}</dd>

                            <dt class="col-sm-4 text-muted">Phone</dt>
                            <dd class="col-sm-8">{{ $record->phone }}</dd>

                            <dt class="col-sm-4 text-muted">Email</dt>
                            <dd class="col-sm-8">{{ $record->email ?: '—' }}</dd>

                            <dt class="col-sm-4 text-muted">Emergency Contact</dt>
                            <dd class="col-sm-8">{{ $record->emergency_contact ?: '—' }}</dd>

                            <dt class="col-sm-4 text-muted">Address</dt>
                            <dd class="col-sm-8">{{ $record->address ?: '—' }}</dd>

                            <dt class="col-sm-4 text-muted">Status</dt>
                            <dd class="col-sm-8"><x-badge :status="$record->status" /></dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white fw-semibold">Clinical</div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4 text-muted">Allergies</dt>
                            <dd class="col-sm-8">{{ $record->allergies ?: 'None recorded' }}</dd>

                            <dt class="col-sm-4 text-muted">Medical History</dt>
                            <dd class="col-sm-8">{{ $record->medical_history ?: 'None recorded' }}</dd>
                        </dl>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-semibold">Activity</div>
                    <div class="card-body d-flex flex-wrap gap-2">
                        <a href="{{ route('appointments.create') }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-calendar-plus me-1"></i>Appointments ({{ $record->appointments->count() }})
                        </a>
                        <a href="{{ route('prescriptions.create') }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-file-earmark-medical me-1"></i>Prescriptions ({{ $record->prescriptions->count() }})
                        </a>
                        <a href="{{ route('invoices.create') }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-receipt me-1"></i>Invoices ({{ $record->invoices->count() }})
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <a href="{{ route('patients.index') }}" class="btn btn-secondary mt-3">
            <i class="bi bi-arrow-left me-1"></i>Back to Patients
        </a>
    </div>
</x-layout>