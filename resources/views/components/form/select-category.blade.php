{{-- $categories: arreglo o colección id => nombre (ej. Category::pluck('nombre', 'id')) --}}
@props(['name' => 'categoria_id', 'label' => 'Categoría', 'categories' => [], 'selected' => null, 'required' => true])

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

    <select id="{{ $id }}"
            name="{{ $name }}"
            @required($required)
            {{ $attributes->except('id')->class(['form-select', 'is-invalid' => $tieneError]) }}>
        <option value="" disabled @selected(old($name, $selected) === null)>Seleccione una categoría</option>
        @foreach ($categories as $catId => $nombre)
            <option value="{{ $catId }}" @selected((string) old($name, $selected) === (string) $catId)>{{ $nombre }}</option>
        @endforeach
    </select>

    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
