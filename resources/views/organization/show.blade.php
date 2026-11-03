@extends('layouts.app')

@section('title', 'Organización')

@section('content')
@include('community._head', ['icon' => 'bi-diagram-3', 'title' => $organization->name, 'subtitle' => 'Tipo de organización, módulos y personas que participan con su propia cuenta.'])

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Tipo de organización</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('organization.kind') }}" class="d-flex gap-2">
                    @csrf
                    <select name="kind" class="form-select">
                        @foreach(\App\Models\Restaurant::KINDS as $k => $label)
                        <option value="{{ $k }}" @selected(($organization->kind ?? 'RESTAURANTE') === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-primary">Guardar</button>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Módulos</h5></div>
            <ul class="list-group list-group-flush">
                @foreach($modules as $m)
                <li class="list-group-item d-flex justify-content-between">
                    <span>{{ $m['label'] }}</span>
                    <span class="badge {{ $m['on'] ? 'bg-success' : 'bg-secondary' }}">{{ $m['on'] ? 'Habilitado' : 'No habilitado' }}</span>
                </li>
                @endforeach
            </ul>
            <div class="card-body small text-muted">Los módulos los habilita el superadministrador desde «Monitor de módulos».</div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Sumar a una persona con su cuenta</h5></div>
            <div class="card-body">
                <p class="small text-muted">Si la persona ya tiene cuenta en el sistema (en otra organización), entra con el mismo usuario y elige esta organización al ingresar. No hace falta crearle otra cuenta.</p>
                <form method="POST" action="{{ route('organization.members.store') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-6">
                        <label class="form-label">Usuario o email</label>
                        <input name="identifier" class="form-control" required value="{{ old('identifier') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Rol en esta organización</label>
                        <select name="role" class="form-select">
                            @foreach($roles as $r)<option value="{{ $r }}">{{ $roleLabels[$r] ?? $r }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><button class="btn btn-primary w-100">Sumar</button></div>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Membresías</h5></div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead><tr><th>Persona</th><th>Rol</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                    @forelse($memberships as $m)
                        <tr>
                            <td>{{ $m->user?->name }} <div class="small text-muted">{{ $m->user?->username }}</div></td>
                            <td colspan="3">
                                <form method="POST" action="{{ route('organization.members.update', $m) }}" class="d-flex gap-2 flex-wrap">
                                    @csrf @method('PATCH')
                                    <select name="role" class="form-select form-select-sm w-auto">
                                        @foreach($roles as $r)<option value="{{ $r }}" @selected($m->role === $r)>{{ $roleLabels[$r] ?? $r }}</option>@endforeach
                                    </select>
                                    <select name="is_active" class="form-select form-select-sm w-auto">
                                        <option value="1" @selected($m->is_active)>Activa</option>
                                        <option value="0" @selected(! $m->is_active)>Suspendida</option>
                                    </select>
                                    <button class="btn btn-sm btn-outline-primary">Guardar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted text-center py-4">Todavía no sumaste personas de otras cuentas.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="mb-0">Todas las personas con acceso ({{ $members->count() }})</h5></div>
            <ul class="list-group list-group-flush">
                @foreach($members as $m)
                <li class="list-group-item d-flex justify-content-between">
                    <span>{{ $m['user']->name }}</span>
                    <span class="small text-muted">{{ $roleLabels[$m['role']] ?? $m['role'] }} · {{ $m['via'] }}</span>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection
