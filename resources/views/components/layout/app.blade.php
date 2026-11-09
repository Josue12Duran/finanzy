@props(['title' => null, 'activeRoute' => null])

@php
    $alertas = [
        'success' => 'success',
        'status'  => 'success',
        'info'    => 'info',
        'warning' => 'warning',
        'error'   => 'danger',
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title . ' | ' : '' }}{{ config('app.name', 'Finanzy') }}</title>

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-light">
    <div class="d-flex min-vh-100 flex-column">

        <x-layout.navbar :user="auth()->user()" />

        <div class="d-flex flex-grow-1">
            <x-layout.sidebar :active-route="$activeRoute ?? request()->path()" />

            <main class="flex-grow-1 p-4 min-w-0">
                @foreach ($alertas as $clave => $tipo)
                    @if (session()->has($clave))
                        <x-ui.alert :type="$tipo" :message="session($clave)" />
                    @endif
                @endforeach

                {{ $slot }}
            </main>
        </div>

        <footer class="bg-white border-top py-3 mt-auto text-center text-muted small">
            &copy; {{ date('Y') }} Finanzy &middot; Sistema de gestión financiera
        </footer>
    </div>

    @stack('scripts')
</body>
</html>
