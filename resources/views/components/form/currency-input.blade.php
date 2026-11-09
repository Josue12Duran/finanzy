@props(['name', 'label' => null, 'value' => null, 'required' => false, 'min' => '0', 'step' => '0.01'])

@php
    $id = $attributes->get('id', $name);
    $tieneError = $errors->has($name);
@endphp

<div class="mb-3">
    @if ($label)
        <label for="{{ $id }}" class="form-label fw-semibold small">
            {{ $label }}@if ($required)<span class="text-danger">*</span>@endif
        </label>
    @endif

    <div class="input-group {{ $tieneError ? 'is-invalid' : '' }}">
        <span class="input-group-text">$</span>
        <input type="number"
               id="{{ $id }}"
               name="{{ $name }}"
               value="{{ old($name, $value) }}"
               min="{{ $min }}"
               step="{{ $step }}"
               inputmode="decimal"
               @required($required)
               {{ $attributes->except('id')->class(['form-control', 'is-invalid' => $tieneError]) }}>
    </div>

    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
