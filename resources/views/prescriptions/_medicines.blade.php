@php
    $selectedIds = collect(old('medicine_ids', $record?->medicines->pluck('id')->all() ?? []));
    $medicineDetails = old('medicine_details', []);
    $rows = $selectedIds->isNotEmpty() ? $selectedIds : collect([null]);
@endphp

<div class="col-12">
    <hr class="my-4">
    <h2 class="h5 mb-3">Prescribed Medicines</h2>

    @error('medicine_ids')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <div id="medicine-rows">
        @foreach ($rows as $medicineId)
            @php($pivot = $record?->medicines?->firstWhere('id', $medicineId)?->pivot?->toArray() ?? [])
            @php($details = $medicineDetails[$medicineId] ?? $pivot)
            <div class="row align-items-end mb-2 medicine-row">
                <div class="col-md-4 mb-2">
                    <label class="form-label">Medicine</label>
                    <select name="medicine_ids[]" class="form-select" required>
                        <option value="">Select a medicine</option>
                        @foreach ($medicines as $medicine)
                            <option value="{{ $medicine->id }}" @selected((string) $medicineId === (string) $medicine->id)>
                                {{ $medicine->name }}{{ $medicine->strength ? ' ('.$medicine->strength.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label">Dosage</label>
                    <input type="text" name="medicine_details[{{ $medicineId }}][dosage]"
                           class="form-control" value="{{ $details['dosage'] ?? '' }}" required>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label">Frequency</label>
                    <input type="text" name="medicine_details[{{ $medicineId }}][frequency]"
                           class="form-control" value="{{ $details['frequency'] ?? '' }}"
                           placeholder="e.g. Twice daily" required>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="form-label">Duration</label>
                    <input type="text" name="medicine_details[{{ $medicineId }}][duration]"
                           class="form-control" value="{{ $details['duration'] ?? '' }}"
                           placeholder="e.g. 5 days" required>
                </div>
                <div class="col-md-1 mb-2">
                    <label class="form-label">Qty</label>
                    <input type="number" name="medicine_details[{{ $medicineId }}][quantity]"
                           class="form-control" value="{{ $details['quantity'] ?? 1 }}" min="1" required>
                </div>
                <div class="col-md-1 mb-2 text-end">
                    <button type="button" class="btn btn-outline-danger remove-medicine" title="Remove">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <button type="button" class="btn btn-sm btn-outline-primary" id="add-medicine">
        <i class="bi bi-plus-lg me-1"></i>Add Medicine
    </button>
</div>
<script>
    (function () {
        const container = document.getElementById('medicine-rows');
        const addButton = document.getElementById('add-medicine');

        if (!container || !addButton) {
            return;
        }

        const options = Array.from(container.querySelector('select[name="medicine_ids[]"]').options)
            .map((option) => `<option value="${option.value}">${option.textContent.trim()}</option>`)
            .join('');

        const rowTemplate = (id) => `
            <div class="col-md-4 mb-2">
                <label class="form-label">Medicine</label>
                <select name="medicine_ids[]" class="form-select" required>
                    <option value="">Select a medicine</option>
                    ${options}
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label">Dosage</label>
                <input type="text" name="medicine_details[${id}][dosage]" class="form-control" required>
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label">Frequency</label>
                <input type="text" name="medicine_details[${id}][frequency]" class="form-control"
                       placeholder="e.g. Twice daily" required>
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label">Duration</label>
                <input type="text" name="medicine_details[${id}][duration]" class="form-control"
                       placeholder="e.g. 5 days" required>
            </div>
            <div class="col-md-1 mb-2">
                <label class="form-label">Qty</label>
                <input type="number" name="medicine_details[${id}][quantity]" class="form-control"
                       value="1" min="1" required>
            </div>
            <div class="col-md-1 mb-2 text-end">
                <button type="button" class="btn btn-outline-danger remove-medicine" title="Remove">
                    <i class="bi bi-trash"></i>
                </button>
            </div>`;

        // Keep medicine_details[<id>] aligned with the medicine chosen on the same row.
        const rekey = (row, id) => {
            row.querySelectorAll('[name^="medicine_details["]').forEach((input) => {
                input.name = input.name.replace(/medicine_details\[[^\]]*\]/, `medicine_details[${id}]`);
            });
        };

        addButton.addEventListener('click', () => {
            const row = document.createElement('div');
            row.className = 'row align-items-end mb-2 medicine-row';
            row.innerHTML = rowTemplate(`new_${Date.now()}`);
            container.appendChild(row);
        });

        container.addEventListener('change', (event) => {
            const select = event.target.closest('select[name="medicine_ids[]"]');

            if (select) {
                rekey(select.closest('.medicine-row'), select.value || 'new');
            }
        });

        container.addEventListener('click', (event) => {
            if (!event.target.closest('.remove-medicine')) {
                return;
            }

            if (container.querySelectorAll('.medicine-row').length > 1) {
                event.target.closest('.medicine-row').remove();
            }
        });
    })();
</script>