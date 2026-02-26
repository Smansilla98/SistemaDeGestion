@extends('layouts.app')

@section('title', 'Tutoriales')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;">
            <i class="bi bi-journal-bookmark"></i> Tutoriales
        </h1>
        <p class="text-muted">Visualiza los manuales y guías en formato PDF.</p>
    </div>
</div>

@if(count($pdfs) > 0)
<div class="row g-4">
    @foreach($pdfs as $pdf)
    <div class="col-md-6 col-lg-4">
        <div class="card h-100">
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-start mb-3">
                    <div class="rounded-3 bg-light d-flex align-items-center justify-content-center me-3" style="width: 56px; height: 56px;">
                        <i class="bi bi-file-earmark-pdf text-danger" style="font-size: 1.75rem;"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <h5 class="card-title mb-1 text-break">{{ Str::title(str_replace(['-', '_'], ' ', $pdf['title'])) }}</h5>
                        <small class="text-muted">
                            {{ number_format($pdf['size'] / 1024, 1) }} KB
                        </small>
                    </div>
                </div>
                <div class="mt-auto">
                    <a href="{{ $pdf['url'] }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm w-100">
                        <i class="bi bi-box-arrow-up-right"></i> Abrir PDF
                    </a>
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100 mt-2 btn-view-here" 
                            data-url="{{ $pdf['url'] }}" 
                            data-title="{{ Str::title(str_replace(['-', '_'], ' ', $pdf['title'])) }}">
                        <i class="bi bi-display"></i> Ver aquí
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<!-- Visor de PDF en página -->
<div class="row mt-4 d-none" id="pdfViewerRow">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0" id="pdfViewerTitle"></h5>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="pdfViewerClose">
                    <i class="bi bi-x-lg"></i> Cerrar
                </button>
            </div>
            <div class="card-body p-0">
                <iframe id="pdfViewerFrame" style="width: 100%; height: 70vh; border: none;" title="Visor PDF"></iframe>
            </div>
        </div>
    </div>
</div>

@else
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-folder2-open text-muted" style="font-size: 4rem;"></i>
        <h5 class="mt-3">No hay tutoriales disponibles</h5>
        <p class="text-muted mb-0">
            Los archivos PDF que se coloquen en la carpeta <code>public/tutoriales</code> del servidor aparecerán aquí.
        </p>
    </div>
</div>
@endif

@if(count($pdfs) > 0)
@push('scripts')
<script>
document.querySelectorAll('.btn-view-here').forEach(btn => {
    btn.addEventListener('click', function() {
        const url = this.dataset.url;
        const title = this.dataset.title;
        document.getElementById('pdfViewerTitle').textContent = title;
        document.getElementById('pdfViewerFrame').src = url;
        document.getElementById('pdfViewerRow').classList.remove('d-none');
        document.getElementById('pdfViewerRow').scrollIntoView({ behavior: 'smooth' });
    });
});
var closeBtn = document.getElementById('pdfViewerClose');
if (closeBtn) closeBtn.addEventListener('click', function() {
    document.getElementById('pdfViewerFrame').src = '';
    document.getElementById('pdfViewerRow').classList.add('d-none');
});
</script>
@endpush
@endif
@endsection
