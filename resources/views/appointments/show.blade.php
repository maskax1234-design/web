<x-layout :title="$record->appointment_number">
    <div class="container">
        <x-page-heading
            :title="$record->appointment_number"
            subtitle="Appointment details"
            :action="route('appointments.edit', $record)"
            action-label="Edit"
        />

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">Overview</div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4 text-muted">Appointment Number</dt>
                            <dd class="col-sm-8 font-monospace">{{ $record->appointment_number }}</dd>

                            <dt class="col-sm-4 text-muted">Patient</dt>
                            <dd class="col-sm-8">
                                @if ($record->patient)
                                    <a href="{{ route('patients.show', $record->patient) }}">
                                        {{ $record->patient->first_name }} {{ $record->patient->last_name }}
                                    </a>
                                    <small class="text-muted">({{ $record->patient->patient_number }})</small>
                                @else
                                    —
                                @endif
                            </dd>

                            <dt class="col-sm-4 text-muted">Doctor</dt>
                            <dd class="col-sm-8">
                                @if ($record->doctor)
                                    <a href="{{ route('doctors.show', $record->doctor) }}">
                                        Dr. {{ $record->doctor->first_name }} {{ $record->doctor->last_name }}
                                    </a>
                                @else
                                    —
                                @endif
                            </dd>

                            <dt class="col-sm-4 text-muted">Department</dt>
                            <dd class="col-sm-8">
                                @if ($record->department)
                                    <a href="{{ route('departments.show', $record->department) }}">{{ $record->department->name }}</a>
                                @else
                                    —
                                @endif
                            </dd>

                            <dt class="col-sm-4 text-muted">Date &amp; Time</dt>
                            <dd class="col-sm-8">
                                {{ $record->appointment_date?->format('d M Y') ?? '—' }}
                                at {{ \Illuminate\Support\Str::substr((string) $record->appointment_time, 0, 5) }}
                            </dd>

                            <dt class="col-sm-4 text-muted">Status</dt>
                            <dd class="col-sm-8"><x-badge :status="$record->status" /></dd>

                            <dt class="col-sm-4 text-muted">Reason</dt>
                            <dd class="col-sm-8">{{ $record->reason ?: '—' }}</dd>

                            <dt class="col-sm-4 text-muted">Notes</dt>
                            <dd class="col-sm-8">{{ $record->notes ?: '—' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">Linked Records</div>
                    <div class="card-body d-flex flex-column gap-2">
                        <a href="{{ route('invoices.create') }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-receipt me-1"></i>Create Invoice
                        </a>
                        <a href="{{ route('prescriptions.create') }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-file-earmark-medical me-1"></i>Create Prescription
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <a href="{{ route('appointments.index') }}" class="btn btn-secondary mt-3">
            <i class="bi bi-arrow-left me-1"></i>Back to Appointments
        </a>
    </div>
</x-layout>