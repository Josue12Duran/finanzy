@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'required' => false, 'placeholder' => null])

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

    <input type="{{ $type }}"
           id="{{ $id }}"
           name="{{ $name }}"
           value="{{ old($name, $value) }}"
           placeholder="{{ $placeholder }}"
           @required($required)
           {{ $attributes->except('id')->class(['form-control', 'is-invalid' => $tieneError]) }}>

    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
