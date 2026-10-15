<x-layout :title="$record->prescription_number">
    <div class="container">
        <x-page-heading
            :title="$record->prescription_number"
            :subtitle="'Issued on '.($record->prescription_date?->format('d M Y') ?? '—')"
            :action="route('prescriptions.edit', $record)"
            action-label="Edit"
        />

        <div class="row g-3">
            <div class="col-lg-5">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">Overview</div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-5 text-muted">Prescription No.</dt>
                            <dd class="col-sm-7 font-monospace">{{ $record->prescription_number }}</dd>

                            <dt class="col-sm-5 text-muted">Patient</dt>
                            <dd class="col-sm-7">
                                @if ($record->patient)
                                    <a href="{{ route('patients.show', $record->patient) }}">
                                        {{ $record->patient->first_name }} {{ $record->patient->last_name }}
                                    </a>
                                @else
                                    —
                                @endif
                            </dd>

                            <dt class="col-sm-5 text-muted">Doctor</dt>
                            <dd class="col-sm-7">
                                @if ($record->doctor)
                                    <a href="{{ route('doctors.show', $record->doctor) }}">
                                        Dr. {{ $record->doctor->first_name }} {{ $record->doctor->last_name }}
                                    </a>
                                @else
                                    —
                                @endif
                            </dd>

                            <dt class="col-sm-5 text-muted">Date</dt>
                            <dd class="col-sm-7">{{ $record->prescription_date?->format('d M Y') ?? '—' }}</dd>

                            <dt class="col-sm-5 text-muted">Status</dt>
                            <dd class="col-sm-7"><x-badge :status="$record->status" /></dd>

                            <dt class="col-sm-5 text-muted">Instructions</dt>
                            <dd class="col-sm-7">{{ $record->instructions ?: '—' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">Prescribed Medicines</div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Medicine</th>
                                    <th scope="col">Dosage</th>
                                    <th scope="col">Frequency</th>
                                    <th scope="col">Duration</th>
                                    <th scope="col" class="text-end">Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($record->medicines as $medicine)
                                    <tr>
                                        <td class="fw-semibold">{{ $medicine->name }}</td>
                                        <td>{{ $medicine->pivot->dosage }}</td>
                                        <td>{{ $medicine->pivot->frequency }}</td>
                                        <td>{{ $medicine->pivot->duration }}</td>
                                        <td class="text-end">{{ $medicine->pivot->quantity }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">No medicines on this prescription.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <a href="{{ route('prescriptions.index') }}" class="btn btn-secondary mt-3">
            <i class="bi bi-arrow-left me-1"></i>Back to Prescriptions
        </a>
    </div>
</x-layout>