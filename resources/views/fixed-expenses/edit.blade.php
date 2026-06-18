@extends('layouts.app')

@section('title', 'Editar Gasto/Ingreso Fijo')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;">
            <i class="bi bi-pencil"></i> Editar Gasto/Ingreso Fijo
        </h1>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('fixed-expenses.update', $fixedExpense) }}" method="POST">
            @csrf
            @method('PUT')
            @include('fixed-expenses._form', ['fixedExpense' => $fixedExpense])
            <div class="d-flex justify-content-between">
                <a href="{{ route('fixed-expenses.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Actualizar
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
