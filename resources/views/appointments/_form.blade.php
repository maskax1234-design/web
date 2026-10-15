@php
    $statuses = ['Scheduled', 'Confirmed', 'Completed', 'Cancelled', 'No Show'];
@endphp

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

<x-form.select
    name="department_id"
    label="Department"
    :options="$selectOptions['department_id'] ?? collect()"
    :value="$record?->department_id"
    :required="true"
/>

<x-form.input
    name="appointment_date"
    label="Appointment Date"
    type="date"
    :value="$record?->appointment_date"
    :required="true"
/>

<x-form.input
    name="appointment_time"
    label="Appointment Time"
    type="time"
    format="H:i"
    :value="$record?->appointment_time"
    :required="true"
/>

<x-form.textarea name="reason" label="Reason for Visit" :value="$record?->reason" :rows="3" :required="true" />
<x-form.select name="status" label="Status" :options="$statuses" :value="$record?->status" :required="true" />
<x-form.textarea name="notes" label="Notes" :value="$record?->notes" :rows="2" />