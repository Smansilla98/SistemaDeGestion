@extends('layouts.app')

@section('title', 'Medios de cobro')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('configuration.index') }}" class="text-white-50 small"><i class="bi bi-arrow-left"></i> Configuración</a>
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.25rem;"><i class="bi bi-credit-card-2-front"></i> Medios de cobro</h1>
        <p class="text-white-50 mb-0">Estos son los medios que va a poder elegir el mozo al cobrar una mesa. Desactivá los que no uses.</p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row">
    @foreach($rows as $row)
    <div class="col-lg-6 mb-4">
        <div class="card h-100 {{ $row->is_active ? 'border-success' : '' }}">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi {{ ['EFECTIVO' => 'bi-cash', 'DEBITO' => 'bi-credit-card', 'CREDITO' => 'bi-credit-card-2-front', 'TRANSFERENCIA' => 'bi-bank', 'QR' => 'bi-qr-code'][$row->type] ?? 'bi-wallet2' }}"></i>
                    {{ $row->label }}
                </h5>
                <span class="badge {{ $row->is_active ? 'bg-success' : 'bg-secondary' }}">
                    {{ $row->is_active ? 'Activo' : 'Inactivo' }}
                </span>
            </div>
            <div class="card-body">
                <form action="{{ route('configuration.payment-methods.update', $row->type) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch"
                               id="active_{{ $row->type }}" name="is_active" value="1"
                               {{ $row->is_active ? 'checked' : '' }}>
                        <label class="form-check-label" for="active_{{ $row->type }}">Disponible en el POS</label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nombre visible</label>
                        <input type="text" class="form-control" name="label" value="{{ $row->label }}" maxlength="255">
                    </div>

                    @if($row->type === 'TRANSFERENCIA')
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label">Alias</label>
                            <input type="text" class="form-control" name="alias" value="{{ $row->alias }}" placeholder="negocio.mp">
                        </div>
                        <div class="col-6">
                            <label class="form-label">CUIT</label>
                            <input type="text" class="form-control" name="cuit" value="{{ $row->cuit }}" placeholder="20-12345678-9">
                        </div>
                        <div class="col-6">
                            <label class="form-label">CVU</label>
                            <input type="text" class="form-control" name="cvu" value="{{ $row->cvu }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label">CBU</label>
                            <input type="text" class="form-control" name="cbu" value="{{ $row->cbu }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Titular</label>
                            <input type="text" class="form-control" name="account_holder" value="{{ $row->account_holder }}">
                        </div>
                    </div>
                    @endif

                    @if($row->type === 'QR')
                    <div class="mb-3">
                        <label class="form-label">Imagen del QR</label>
                        @if($row->qr_image_url)
                        <div class="mb-2">
                            <img src="{{ $row->qr_image_url }}" alt="QR" style="max-height: 140px;" class="img-thumbnail d-block mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remove_qr_{{ $row->type }}" name="remove_qr_image" value="1">
                                <label class="form-check-label small" for="remove_qr_{{ $row->type }}">Quitar QR actual</label>
                            </div>
                        </div>
                        @endif
                        <input type="file" class="form-control" name="qr_image" accept="image/*">
                        <small class="form-text text-muted">JPG, PNG o WEBP. Máximo 2MB. Es un QR estático — el mismo para todas las cajas.</small>
                    </div>
                    @endif

                    @if(in_array($row->type, ['TRANSFERENCIA', 'QR']))
                    <div class="mb-3">
                        <label class="form-label">Instrucciones para el mozo (opcional)</label>
                        <textarea class="form-control" name="instructions" rows="2" maxlength="1000">{{ $row->instructions }}</textarea>
                    </div>
                    @endif

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Guardar
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection
