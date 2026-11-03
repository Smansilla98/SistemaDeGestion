{{-- Encabezado de página de los módulos de organizaciones. --}}
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        @isset($back)<a href="{{ $back }}" class="btn btn-secondary btn-sm mb-2"><i class="bi bi-arrow-left"></i> Volver</a>@endisset
        <h1 class="text-white mb-1 page-hero-title"><i class="bi {{ $icon ?? 'bi-circle' }}"></i> {{ $title }}</h1>
        @isset($subtitle)<p class="text-white-50 mb-0">{{ $subtitle }}</p>@endisset
    </div>
    @isset($actions)<div class="d-flex gap-2 flex-wrap">{!! $actions !!}</div>@endisset
</div>
@include('community._alerts')
