@php
    $genders = ['Male', 'Female', 'Other'];
    $bloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    $statuses = ['Active', 'Inactive', 'Deceased'];
@endphp

<div class="col-md-6 mb-3">
    <div class="row">
        <x-form.input name="first_name" label="First Name" :value="$record?->first_name" :required="true" col="col-md-6" />
        <x-form.input name="last_name" label="Last Name" :value="$record?->last_name" :required="true" col="col-md-6" />
    </div>
</div>

<x-form.select name="gender" label="Gender" :options="$genders" :value="$record?->gender" :required="true" />
<x-form.input
    name="date_of_birth"
    label="Date of Birth"
    type="date"
    :value="$record?->date_of_birth"
    :required="true"
    :max="now()->toDateString()"
/>

<x-form.input name="phone" label="Phone" type="tel" :value="$record?->phone" :required="true" />
<x-form.input name="email" label="Email" type="email" :value="$record?->email" />
<x-form.input name="blood_group" label="Blood Group" :options="$bloodGroups" :value="$record?->blood_group" placeholder="Unknown" />
<x-form.input name="emergency_contact" label="Emergency Contact" :value="$record?->emergency_contact" />

<x-form.textarea name="address" label="Address" :value="$record?->address" :rows="2" />
<x-form.textarea name="allergies" label="Allergies" :value="$record?->allergies" :rows="2" />
<x-form.textarea name="medical_history" label="Medical History" :value="$record?->medical_history" :rows="3" />
<x-form.select name="status" label="Status" :options="$statuses" :value="$record?->status" :required="true" />