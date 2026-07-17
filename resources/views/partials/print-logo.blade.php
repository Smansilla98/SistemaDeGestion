@php
    use App\Support\Branding;

    $printSettings = $settings
        ?? ($restaurant->settings ?? null)
        ?? (isset($order) ? ($order->restaurant->settings ?? null) : null)
        ?? (isset($table) ? ($table->restaurant->settings ?? null) : null);

    $useAbsolutePath = $useAbsolutePath ?? true;
    $logoSrc = $useAbsolutePath
        ? Branding::logoPath(is_array($printSettings) ? $printSettings : null)
        : Branding::logoUrl(is_array($printSettings) ? $printSettings : null);
@endphp
@if($logoSrc)
<div class="logo-container">
    <img src="{{ $logoSrc }}" alt="{{ Branding::name() }}">
</div>
@endif
