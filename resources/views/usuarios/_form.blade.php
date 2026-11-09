{{--
    Formulario compartido de create/edit.
    Requiere: $usuario, $roles, $rolActual, $action, $method, $submitLabel.
--}}
@php
    $esNuevo = ! $usuario->exists;
@endphp

<form method="POST" action="{{ $action }}" novalidate>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="row g-3">
        <div class="col-12 col-md-6">
            <x-form.input name="name" label="Nombre completo" :value="$usuario->name" required autofocus autocomplete="name" />
        </div>

        <div class="col-12 col-md-6">
            <x-form.input name="email" type="email" label="Correo electrónico" :value="$usuario->email" required autocomplete="off" />
        </div>

        <div class="col-12 col-md-6">
            <div class="mb-3">
                <label for="rol" class="form-label fw-semibold small">
                    Rol<span class="text-danger">*</span>
                </label>
                <select id="rol" name="rol" class="form-select @error('rol') is-invalid @enderror" required>
                    <option value="">Seleccione un rol</option>
                    @foreach ($roles as $rol)
                        <option value="{{ $rol }}" @selected(old('rol', $rolActual) === $rol)>{{ $rol }}</option>
                    @endforeach
                </select>
                @error('rol')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="col-12 col-md-6"></div>

        <div class="col-12 col-md-6">
            <div class="mb-3">
                <label for="password" class="form-label fw-semibold small">
                    Contraseña @if ($esNuevo)<span class="text-danger">*</span>@endif
                </label>
                <input type="password" id="password" name="password" autocomplete="new-password"
                       class="form-control @error('password') is-invalid @enderror"
                       @required($esNuevo)>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                @unless ($esNuevo)
                    <div class="form-text">Déjela vacía para conservar la contraseña actual.</div>
                @endunless
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="mb-3">
                <label for="password_confirmation" class="form-label fw-semibold small">
                    Confirmar contraseña @if ($esNuevo)<span class="text-danger">*</span>@endif
                </label>
                <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                       class="form-control" @required($esNuevo)>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-2">
        <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg"></i> {{ $submitLabel }}
        </button>
    </div>
</form>
