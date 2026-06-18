@extends('layouts.app')

@section('title', 'Nuevo Gasto/Ingreso Fijo')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;">
            <i class="bi bi-cash-stack"></i> Nuevo Gasto/Ingreso Fijo
        </h1>
        <p class="text-white-50 mb-0">Definí el monto, la frecuencia y el día de cobro para proyectar el ingreso fijo mensual.</p>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('fixed-expenses.store') }}" method="POST">
            @csrf
            @include('fixed-expenses._form')
            <div class="d-flex justify-content-between">
                <a href="{{ route('fixed-expenses.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Guardar
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
