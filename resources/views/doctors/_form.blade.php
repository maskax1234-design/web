@php
    $genders = ['Male', 'Female', 'Other'];
    $statuses = ['Active', 'On Leave', 'Inactive'];
@endphp

<div class="col-md-6 mb-3">
    <div class="row">
        <x-form.input name="first_name" label="First Name" :value="$record?->first_name" :required="true" col="col-md-6" />
        <x-form.input name="last_name" label="Last Name" :value="$record?->last_name" :required="true" col="col-md-6" />
    </div>
</div>

<x-form.select name="gender" label="Gender" :options="$genders" :value="$record?->gender" :required="true" />
<x-form.input name="phone" label="Phone" type="tel" :value="$record?->phone" :required="true" />
<x-form.input name="email" label="Email" type="email" :value="$record?->email" :required="true" />
<x-form.input name="specialization" label="Specialization" :value="$record?->specialization" :required="true" />

<x-form.select
    name="department_id"
    label="Department"
    :options="$selectOptions['department_id'] ?? collect()"
    :value="$record?->department_id"
    :required="true"
/>

<x-form.input name="qualification" label="Qualification" :value="$record?->qualification" :required="true" />
<x-form.input name="license_number" label="License Number" :value="$record?->license_number" :required="true" />
<x-form.input
    name="experience"
    label="Experience (years)"
    type="number"
    :value="$record?->experience"
    :min="0"
    :required="true"
/>

<x-form.select name="status" label="Status" :options="$statuses" :value="$record?->status" :required="true" />