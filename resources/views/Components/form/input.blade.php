@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'col' => 'col-md-6',
    'format' => 'Y-m-d',
    'step' => null,
    'min' => null,
    'max' => null,
    'placeholder' => null,
])

@php
    $formattedValue = $value instanceof DateTimeInterface ? $value->format($format) : $value;
@endphp

<div class="{{ $col }} mb-3">
    <label class="form-label" for="field-{{ $name }}">
        {{ $label }}
        @if($required)
            <span class="text-danger">*</span>
        @endif
    </label>
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="field-{{ $name }}"
        value="{{ old($name, $formattedValue) }}"
        @if($step !== null) step="{{ $step }}" @endif
        @if($min !== null) min="{{ $min }}" @endif
        @if($max !== null) max="{{ $max }}" @endif
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        @required($required)
        class="form-control @error($name) is-invalid @enderror"
    >
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>