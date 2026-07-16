@props([
    'variant' => 'primary', // primary | secondary | outline | ghost | danger | success
    'size' => 'md', // sm | md
    'type' => 'button',
    'href' => null,
    'loading' => false,
    'icon' => null,
    'iconOnly' => false,
])

@php
    $classes = collect([
        'btn',
        match ($variant) {
            'secondary' => 'btn-secondary',
            'outline' => 'btn-outline',
            'ghost' => 'btn-ghost',
            'danger' => 'btn-danger',
            'success' => 'btn-success',
            default => 'btn-primary',
        },
        $size === 'sm' ? 'btn-sm' : null,
        $iconOnly ? 'btn-icon' : null,
    ])->filter()->implode(' ');

    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    @if(! $href) type="{{ $type }}" @endif
    {{ $attributes->class([$classes])->merge($href ? ['href' => $href] : []) }}
    @if($loading) aria-busy="true" disabled @endif
>
    @if($loading)
        <x-spinner size="sm" />
    @elseif($icon)
        <i class="bi {{ $icon }}" aria-hidden="true"></i>
    @endif
    @unless($iconOnly)
        {{ $slot }}
    @endunless
</{{ $tag }}>
