@php
    $statuses = ['Active', 'Dispensed', 'Cancelled'];
@endphp

<x-form.select
    name="appointment_id"
    label="Appointment"
    :options="$selectOptions['appointment_id'] ?? collect()"
    :value="$record?->appointment_id"
    placeholder="Not linked to an appointment"
/>

<x-form.select
    name="patient_id"
    label="Patient"
    :options="$selectOptions['patient_id'] ?? collect()"
    :value="$record?->patient_id"
    :required="true"
/>

<x-form.select
    name="doctor_id"
    label="Doctor"
    :options="$selectOptions['doctor_id'] ?? collect()"
    :value="$record?->doctor_id"
    :required="true"
/>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const appointmentSelect = document.querySelector('select[name="appointment_id"]');
        const patientSelect = document.querySelector('select[name="patient_id"]');
        const doctorSelect = document.querySelector('select[name="doctor_id"]');

        if (!appointmentSelect || !patientSelect || !doctorSelect) {
            return;
        }

        const appointmentMap = @json($appointments ?? []);

        const fillFromAppointment = () => {
            const appointmentId = appointmentSelect.value;
            const appointment = appointmentMap[appointmentId] ?? null;

            if (!appointment) {
                return;
            }

            if (appointment.patient_id) {
                patientSelect.value = String(appointment.patient_id);
            }

            if (appointment.doctor_id) {
                doctorSelect.value = String(appointment.doctor_id);
            }
        };

        appointmentSelect.addEventListener('change', fillFromAppointment);

        if (appointmentSelect.value) {
            fillFromAppointment();
        }
    });
</script>

@php
    $defaultPrescriptionDate = $record?->prescription_date ?? now()->toDateString();
@endphp

<x-form.input
    name="prescription_date"
    label="Prescription Date"
    type="date"
    :value="$defaultPrescriptionDate"
    :required="true"
/>

<x-form.select name="status" label="Status" :options="$statuses" :value="$record?->status" :required="true" />
<x-form.textarea name="instructions" label="Instructions" :value="$record?->instructions" :rows="2" />

@include('prescriptions._medicines')