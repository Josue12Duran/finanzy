@props(['name' => 'fecha', 'label' => 'Fecha', 'value' => null, 'required' => true])

@php
    $id = $attributes->get('id', $name);
    $tieneError = $errors->has($name);
    $valor = old($name, $value ?? date('Y-m-d'));
@endphp

<div class="mb-3">
    @if ($label)
        <label for="{{ $id }}" class="form-label fw-semibold small">
            {{ $label }}@if ($required)<span class="text-danger">*</span>@endif
        </label>
    @endif

    <input type="date"
           id="{{ $id }}"
           name="{{ $name }}"
           value="{{ $valor }}"
           @required($required)
           {{ $attributes->except('id')->class(['form-control', 'is-invalid' => $tieneError]) }}>

    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
