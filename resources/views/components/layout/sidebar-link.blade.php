@props(['modulo', 'activeRoute' => null])

@php
    $activo = $activeRoute !== null
        && str_starts_with(ltrim($activeRoute, '/'), $modulo['ruta']);
@endphp

<a href="{{ url('/' . $modulo['ruta']) }}"
   @class(['sidebar-link', 'is-active' => $activo])
   @if ($activo) aria-current="page" @endif>
    <i class="bi {{ $modulo['icono'] }} sidebar-link__icon" aria-hidden="true"></i>
    <span>{{ $modulo['etiqueta'] }}</span>
</a>
