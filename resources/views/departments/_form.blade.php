@php
    $statuses = ['Active', 'Inactive'];
@endphp

<x-form.input name="name" label="Department Name" :value="$record?->name" :required="true" />
<x-form.textarea name="description" label="Description" :value="$record?->description" :rows="4" />
<x-form.select name="status" label="Status" :options="$statuses" :value="$record?->status" :required="true" />