@props([
    'tone' => 'teal', // green | amber | teal | red | blue | gray
    'dot' => false,
])

@php
    $toneClass = match ($tone) {
        'green', 'success' => 'bg-green',
        'amber', 'warning' => 'bg-amber',
        'red', 'danger', 'error' => 'bg-red',
        'blue', 'info' => 'bg-blue',
        'gray', 'secondary' => 'bg-gray',
        default => 'bg-teal',
    };
@endphp

<span {{ $attributes->class(['badge', $toneClass]) }}>
    @if($dot)
        <i class="bi bi-circle-fill" style="font-size: 6px;" aria-hidden="true"></i>
    @endif
    {{ $slot }}
</span>
