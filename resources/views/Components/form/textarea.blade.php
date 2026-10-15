@props([
    'name',
    'label',
    'value' => null,
    'required' => false,
    'col' => 'col-md-12',
    'rows' => 3,
    'placeholder' => null,
])

<div class="{{ $col }} mb-3">
    <label class="form-label" for="field-{{ $name }}">
        {{ $label }}
        @if($required)
            <span class="text-danger">*</span>
        @endif
    </label>
    <textarea
        name="{{ $name }}"
        id="field-{{ $name }}"
        rows="{{ $rows }}"
        @required($required)
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        class="form-control @error($name) is-invalid @enderror"
    >{{ old($name, $value) }}</textarea>
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>