@php
    $categoryOptions = ['Analgesic', 'Antibiotic', 'Antihistamine', 'Cardiovascular', 'Dermatology', 'Endocrine', 'Ophthalmic', 'Psychiatric', 'Respiratory', 'Vitamin'];
    $dosageFormOptions = ['Tablet', 'Capsule', 'Syrup', 'Suspension', 'Injection', 'Ointment', 'Cream', 'Gel', 'Drops', 'Inhaler', 'Suppository'];
    $statusOptions = ['Active', 'Inactive', 'Expired'];
    $categories = array_combine($categoryOptions, $categoryOptions);
    $dosageForms = array_combine($dosageFormOptions, $dosageFormOptions);
    $statuses = array_combine($statusOptions, $statusOptions);
@endphp

<x-form.input name="name" label="Name" :value="$record?->name" :required="true" />
<x-form.input name="generic_name" label="Generic Name" :value="$record?->generic_name" />
<x-form.select name="category" label="Category" :options="$categories" :value="$record?->category" placeholder="Not categorised" />
<x-form.input name="manufacturer" label="Manufacturer" :value="$record?->manufacturer" />
<x-form.select name="dosage_form" label="Dosage Form" :options="$dosageForms" :value="$record?->dosage_form" :required="true" />
<x-form.input name="strength" label="Strength" :value="$record?->strength" placeholder="e.g. 500 mg" />

<x-form.input
    name="quantity"
    label="Quantity in Stock"
    type="number"
    :value="$record?->quantity"
    :min="0"
    :required="true"
/>

<x-form.input
    name="unit_price"
    label="Unit Price"
    type="number"
    step="0.01"
    :value="$record?->unit_price"
    :min="0"
    :required="true"
/>

<x-form.input
    name="expiry_date"
    label="Expiry Date"
    type="date"
    :value="$record?->expiry_date"
    :required="true"
/>

<x-form.select name="status" label="Status" :options="$statuses" :value="$record?->status" :required="true" />