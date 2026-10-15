<x-layout :title="$record->name">
    <div class="container">
        <x-page-heading
            :title="$record->name"
            :subtitle="$record->medicine_code.($record->strength ? ' • '.$record->strength : '')"
            :action="route('medicines.edit', $record)"
            action-label="Edit"
        />

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">Overview</div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4 text-muted">Medicine Code</dt>
                            <dd class="col-sm-8 font-monospace">{{ $record->medicine_code }}</dd>

                            <dt class="col-sm-4 text-muted">Generic Name</dt>
                            <dd class="col-sm-8">{{ $record->generic_name ?: '—' }}</dd>

                            <dt class="col-sm-4 text-muted">Category</dt>
                            <dd class="col-sm-8">{{ $record->category ?: '—' }}</dd>

                            <dt class="col-sm-4 text-muted">Manufacturer</dt>
                            <dd class="col-sm-8">{{ $record->manufacturer ?: '—' }}</dd>

                            <dt class="col-sm-4 text-muted">Dosage Form</dt>
                            <dd class="col-sm-8">{{ $record->dosage_form }}</dd>

                            <dt class="col-sm-4 text-muted">Strength</dt>
                            <dd class="col-sm-8">{{ $record->strength ?: '—' }}</dd>

                            <dt class="col-sm-4 text-muted">Expiry Date</dt>
                            <dd class="col-sm-8">{{ $record->expiry_date?->format('d M Y') ?? '—' }}</dd>

                            <dt class="col-sm-4 text-muted">Status</dt>
                            <dd class="col-sm-8"><x-badge :status="$record->status" /></dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">Stock</div>
                    <div class="card-body text-center">
                        <div class="display-6">{{ $record->quantity }}</div>
                        <p class="text-muted mb-3">units available</p>
                        <div class="fs-5">Unit Price: <strong>{{ number_format((float) $record->unit_price, 2) }}</strong></div>
                    </div>
                </div>
            </div>
        </div>

        <a href="{{ route('medicines.index') }}" class="btn btn-secondary mt-3">
            <i class="bi bi-arrow-left me-1"></i>Back to Medicines
        </a>
    </div>
</x-layout>