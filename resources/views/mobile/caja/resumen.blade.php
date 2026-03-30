@extends('layouts.mobile')

@section('title', 'Caja · Resumen')

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h2 class="h4 mb-1">Caja</h2>
            <div class="small text-muted">Resumen del día {{ $dateLabel }}</div>
        </div>
        <a href="{{ route('cash-register.index') }}" class="btn btn-sm btn-outline-light">
            <i class="bi bi-box-arrow-up-right me-1"></i> Módulo
        </a>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-6">
            <div class="card bg-dark border-0 text-white h-100">
                <div class="card-body py-3">
                    <div class="small text-white-50">Ventas</div>
                    <div class="fw-bold fs-5">${{ number_format($totals['payments'] ?? 0, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="card bg-dark border-0 text-white h-100">
                <div class="card-body py-3">
                    <div class="small text-white-50">Neto</div>
                    <div class="fw-bold fs-5">${{ number_format($totals['neto'] ?? 0, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="card bg-dark border-0 text-white h-100">
                <div class="card-body py-3">
                    <div class="small text-white-50">Ingresos</div>
                    <div class="fw-bold fs-5">${{ number_format($totals['ingresos'] ?? 0, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="card bg-dark border-0 text-white h-100">
                <div class="card-body py-3">
                    <div class="small text-white-50">Egresos</div>
                    <div class="fw-bold fs-5">${{ number_format($totals['egresos'] ?? 0, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card bg-dark border-0 text-white">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="fw-semibold">Sesiones</div>
                <span class="badge bg-secondary">{{ $sessions->count() }}</span>
            </div>

            @if($sessions->count() === 0)
                <div class="text-white-50 small">No hay sesiones de caja registradas hoy.</div>
            @else
                <div class="d-flex flex-column gap-2">
                    @foreach($sessions as $s)
                        <a href="{{ route('cash-register.session', $s) }}" class="text-decoration-none text-white">
                            <div class="p-3 rounded-3" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(148,163,184,0.25);">
                                <div class="d-flex align-items-center justify-content-between gap-2">
                                    <div class="fw-semibold text-truncate">
                                        <i class="bi bi-cash-coin me-1"></i>{{ $s->cashRegister->name ?? 'Caja' }}
                                    </div>
                                    <span class="badge {{ ($s->status ?? '') === 'ABIERTA' ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $s->status ?? '-' }}
                                    </span>
                                </div>
                                <div class="small text-white-50 mt-1">
                                    {{ $s->opened_at?->format('H:i') ?? '--:--' }}
                                    @if($s->closed_at) — {{ $s->closed_at->format('H:i') }} @endif
                                    · {{ $s->user->name ?? '—' }}
                                </div>
                                <div class="row g-2 mt-2">
                                    <div class="col-6">
                                        <div class="small text-white-50">Ventas</div>
                                        <div class="fw-semibold">${{ number_format($s->total_payments ?? 0, 2) }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="small text-white-50">Esperado</div>
                                        <div class="fw-semibold">${{ number_format($s->expected_amount ?? 0, 2) }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="small text-white-50">Ingresos</div>
                                        <div class="fw-semibold">${{ number_format($s->total_ingresos ?? 0, 2) }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="small text-white-50">Egresos</div>
                                        <div class="fw-semibold">${{ number_format($s->total_egresos ?? 0, 2) }}</div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

