@props(['type' => 'info', 'message' => null, 'dismissible' => true])

<div {{ $attributes->class(['alert', "alert-{$type}", 'alert-dismissible' => $dismissible, 'fade', 'show', 'd-flex', 'align-items-center', 'gap-2']) }} role="alert">
    <i class="bi {{ match ($type) {
        'success' => 'bi-check-circle-fill',
        'warning' => 'bi-exclamation-triangle-fill',
        'danger'  => 'bi-x-octagon-fill',
        default   => 'bi-info-circle-fill',
    } }}"></i>

    <div class="flex-grow-1">
        {{ $message ?? $slot }}
    </div>

    @if ($dismissible)
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    @endif
</div>
