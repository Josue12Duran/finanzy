@props(['title' => null, 'chartId', 'height' => '300px'])

<div class="card h-100 border-0 shadow-sm">
    @if ($title)
        <div class="card-header bg-white fw-semibold">{{ $title }}</div>
    @endif

    <div class="card-body">
        <div class="position-relative w-100" style="height: {{ $height }};">
            <canvas id="{{ $chartId }}" role="img" aria-label="{{ $title ?? 'Gráfico financiero' }}"></canvas>
        </div>
    </div>
</div>
