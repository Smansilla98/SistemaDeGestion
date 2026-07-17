@props([
    'settings' => null,
    'variant' => 'sidebar', // sidebar|auth|compact
    'maxHeight' => null,
])

@php
    use App\Support\Branding;

    $brandName = Branding::name();
    $logoUrl = Branding::logoUrl($settings);
    $height = $maxHeight ?? ($variant === 'auth' ? '64px' : '42px');
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
            <i class="bi bi-cup-hot"></i>
        </span>
    </div>
@endif
