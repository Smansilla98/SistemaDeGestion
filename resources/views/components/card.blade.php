@props([
    'href' => null,
    'icon' => null,
    'label' => null,
    'value' => null,
    'subvalue' => null,
    'variant' => 'stat', // stat | panel
])

@php
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} {{ $attributes->class([
    'sc' => $variant === 'stat',
    'card' => $variant === 'panel',
])->merge($href ? ['href' => $href] : []) }}>
    @if($variant === 'stat')
        @if($icon)
            <div class="si"><i class="bi {{ $icon }}" aria-hidden="true"></i></div>
        @endif
        @if($label)
            <div class="sl">{{ $label }}</div>
        @endif
        @if($value !== null)
            <div class="sv">
                {{ $value }}
                @if($subvalue)<span>{{ $subvalue }}</span>@endif
            </div>
        @endif
        {{ $slot }}
    @else
        {{ $slot }}
    @endif
</{{ $tag }}>
