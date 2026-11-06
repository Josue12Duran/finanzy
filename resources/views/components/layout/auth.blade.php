@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' | ' : '' }}Finanzy</title>

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-light">
    <main class="container min-vh-100 d-flex align-items-center justify-content-center py-5">
        <div class="row justify-content-center w-100">
            <div class="col-12 col-sm-10 col-md-7 col-lg-5">
                <div class="text-center mb-4">
                    <a href="{{ url('/') }}" class="text-decoration-none text-dark fw-bold fs-3 d-inline-flex align-items-center gap-2">
                        <i class="bi bi-wallet2"></i> Finanzy
                    </a>
                </div>

                <div class="card shadow-sm border-0">
                    @if ($title)
                        <div class="card-header bg-white fw-semibold py-3">{{ $title }}</div>
                    @endif

                    <div class="card-body p-4">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </main>

    @stack('scripts')
</body>
</html>
