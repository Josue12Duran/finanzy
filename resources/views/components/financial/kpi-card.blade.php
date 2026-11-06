@props(['title', 'amount', 'icon' => 'bi-cash-stack', 'type' => 'primary', 'percentage' => null])

@php
    $montoFormateado = '$' . number_format((float) $amount, 2, '.', ',');
    $esPositivo = $percentage !== null && (float) $percentage >= 0;
@endphp

<div class="card h-100 border-0 shadow-sm border-start border-4 border-{{ $type }}">
    <div class="card-body d-flex align-items-center gap-3">
        <div class="rounded-3 bg-{{ $type }}-subtle text-{{ $type }} d-flex align-items-center justify-content-center flex-shrink-0"
             style="width: 48px; height: 48px;">
            <i class="bi {{ $icon }} fs-4"></i>
        </div>

        <div class="flex-grow-1">
            <div class="text-muted small text-uppercase fw-semibold">{{ $title }}</div>
            <div class="fs-4 fw-bold">{{ $montoFormateado }}</div>

            @if ($percentage !== null)
                <div class="small {{ $esPositivo ? 'text-success' : 'text-danger' }}">
                    <i class="bi {{ $esPositivo ? 'bi-arrow-up-right' : 'bi-arrow-down-right' }}"></i>
                    {{ number_format((float) $percentage, 1) }}%
                    <span class="text-muted">vs. periodo anterior</span>
                </div>
            @endif
        </div>
    </div>
</div>
