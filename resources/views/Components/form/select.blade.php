@props([
    'name',
    'label',
    'options' => [],
    'value' => null,
    'required' => false,
    'col' => 'col-md-6',
    'placeholder' => 'Select an option',
])

<div class="{{ $col }} mb-3">
    <label class="form-label" for="field-{{ $name }}">
        {{ $label }}
        @if($required)
            <span class="text-danger">*</span>
        @endif
    </label>
    <select
        name="{{ $name }}"
        id="field-{{ $name }}"
        @required($required)
        class="form-select @error($name) is-invalid @enderror"
    >
        <option value="">{{ $placeholder }}</option>
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) old($name, $value) === (string) $optionValue)>
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>