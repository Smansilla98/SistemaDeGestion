@props([
    'settings' => null,
    'variant' => 'sidebar', // sidebar|auth|compact
    'maxHeight' => null,
    'onLight' => false, // true: versión negra, para fondos claros
])

@php
    use App\Support\Branding;

    $brandName = Branding::name();
    $logoUrl = Branding::logoUrl($settings, (bool) $onLight);
    $height = $maxHeight ?? ($variant === 'auth' ? '110px' : '58px');
@endphp

@if($logoUrl)
    <img
        src="{{ $logoUrl }}"
        alt="{{ $brandName }}"
        {{ $attributes->merge([
            'class' => 'brand-logo-img',
            'style' => "max-height: {$height}; width: auto; display: block;",
        ]) }}
    >
@else
    <div
        {{ $attributes->merge([
            'class' => 'brand-logo-fallback brand-logo-fallback--'.$variant,
            'role' => 'img',
            'aria-label' => $brandName,
        ]) }}
    >
        <span class="brand-logo-mark" aria-hidden="true">
            <i class="bi bi-easel"></i>
        </span>
        @if($variant === 'sidebar')
            <span class="brand-logo-text">
                <span class="brand-logo-name">{{ $brandName }}</span>
                <span class="brand-logo-tag">{{ \App\Support\Branding::tagline() }}</span>
            </span>
        @endif
    </div>
@endif
