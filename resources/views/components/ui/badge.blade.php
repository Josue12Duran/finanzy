@props(['type' => 'secondary', 'pill' => true])

<span {{ $attributes->class(['badge', "bg-{$type}", 'rounded-pill' => $pill]) }}>
    {{ $slot }}
</span>
