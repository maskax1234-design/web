@php
    $statuses = ['Unpaid', 'Partial', 'Paid', 'Cancelled'];
@endphp

<x-form.select
    name="prescription_id"
    label="Prescription"
    :options="$selectOptions['prescription_id'] ?? collect()"
    :value="$record?->prescription_id"
    placeholder="Select a prescription"
/>

<x-form.select
    name="patient_id"
    label="Patient"
    :options="$selectOptions['patient_id'] ?? collect()"
    :value="$record?->patient_id"
    :required="true"
/>

<x-form.select
    name="appointment_id"
    label="Appointment"
    :options="$selectOptions['appointment_id'] ?? collect()"
    :value="$record?->appointment_id"
    placeholder="Not linked to an appointment"
/>

<x-form.input
    name="invoice_date"
    label="Invoice Date"
    type="date"
    :value="$record?->invoice_date ?? now()->toDateString()"
    :required="true"
/>

<div class="col-md-12 mb-3">
    <div class="row">
        <x-form.input name="discount" label="Discount" type="number" step="0.01" :min="0"
                      :value="$record?->discount ?? 0" col="col-md-6" />
        <x-form.input name="tax_percent" label="Tax %" type="number" step="0.01" :min="0"
                      :value="$record?->tax_percent ?? 0" col="col-md-6" />
        <div class="col-md-6 mb-3">
            <label class="form-label" for="invoice-total-preview">Estimated Total</label>
            <input
                type="number"
                id="invoice-total-preview"
                class="form-control fw-semibold"
                value="0.00"
                step="0.01"
                readonly
            >
            <div class="form-text">Updates when you select a prescription or change discount and tax.</div>
        </div>
    </div>
</div>

<x-form.textarea name="notes" label="Notes" :value="$record?->notes" :rows="2" />

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const prescriptionSelect = document.querySelector('select[name="prescription_id"]');
        const patientSelect = document.querySelector('select[name="patient_id"]');
        const appointmentSelect = document.querySelector('select[name="appointment_id"]');
        const discountInput = document.querySelector('input[name="discount"]');
        const taxInput = document.querySelector('input[name="tax_percent"]');
        const totalPreview = document.getElementById('invoice-total-preview');

        if (!prescriptionSelect || !patientSelect || !appointmentSelect || !discountInput || !taxInput || !totalPreview) {
            return;
        }

        const prescriptionMap = @json($prescriptionMap ?? []);

        const updateTotalPreview = () => {
            const prescription = prescriptionMap[prescriptionSelect.value] ?? null;
            const subtotal = Number(prescription?.subtotal ?? 0);
            const discount = Number(discountInput.value) || 0;
            const taxPercent = Number(taxInput.value) || 0;
            const discountedAmount = Math.max(0, subtotal - discount);
            const total = discountedAmount + (discountedAmount * taxPercent / 100);

            totalPreview.value = (Math.round((total + Number.EPSILON) * 100) / 100).toFixed(2);
        };

        const fillFromPrescription = () => {
            const prescription = prescriptionMap[prescriptionSelect.value] ?? null;

            if (prescription) {
                patientSelect.value = String(prescription.patient_id);
                appointmentSelect.value = prescription.appointment_id ? String(prescription.appointment_id) : '';
            }

            updateTotalPreview();
        };

        prescriptionSelect.addEventListener('change', fillFromPrescription);
        discountInput.addEventListener('input', updateTotalPreview);
        taxInput.addEventListener('input', updateTotalPreview);

        fillFromPrescription();
    });
</script>